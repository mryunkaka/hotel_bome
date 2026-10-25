<?php

namespace App\Services\Finance;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * FolioService handles ledger postings, payments, refunds, reversals,
 * and immutable invoice snapshots.
 *
 * Invariants:
 * - Append-only ledger: no UPDATE or DELETE on folio_charges / folio_payments.
 * - Compensating entries for adjustments and reversals.
 * - Idempotency on payment creation via unique property_id + idempotency_key.
 * - Multi-tenant isolation by property_id.
 * - Fixed precision integer currency (IDR per D-004 provisional).
 */
class FolioService
{
    /**
     * Get existing open folio for reservation or create a new one.
     */
    public function getOrCreateFolioForReservation(int $reservationId, Property $property): int
    {
        return DB::transaction(function () use ($reservationId, $property): int {
            $reservation = DB::table('reservations')
                ->where('id', $reservationId)
                ->where('property_id', $property->id)
                ->first();

            if ($reservation === null) {
                throw new \DomainException('Reservation not found in this property.');
            }

            $folio = DB::table('folios')
                ->where('reservation_id', $reservationId)
                ->where('property_id', $property->id)
                ->where('status', 'open')
                ->first();

            if ($folio !== null) {
                return (int) $folio->id;
            }

            $now = now();

            return (int) DB::table('folios')->insertGetId([
                'property_id' => $property->id,
                'reservation_id' => $reservationId,
                'guest_id' => $reservation->guest_id,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Post a charge to a folio.
     *
     * @param  array{amount: int, description: string, item_type?: string, actor_id: int}  $data
     */
    public function postCharge(int $folioId, Property $property, array $data): int
    {
        return DB::transaction(function () use ($folioId, $property, $data): int {
            $this->guardFolio($folioId, $property);

            return (int) DB::table('folio_charges')->insertGetId([
                'folio_id' => $folioId,
                'property_id' => $property->id,
                'amount' => $data['amount'],
                'description' => $data['description'],
                'item_type' => $data['item_type'] ?? 'room',
                'reversal_of_id' => null,
                'reason' => null,
                'posted_by' => $data['actor_id'],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Reverse a previous charge with a compensating negative record.
     */
    public function reverseCharge(int $folioId, Property $property, int $chargeId, string $reason, int $actorId): int
    {
        return DB::transaction(function () use ($folioId, $property, $chargeId, $reason, $actorId): int {
            $this->guardFolio($folioId, $property);

            $original = DB::table('folio_charges')
                ->where('id', $chargeId)
                ->where('folio_id', $folioId)
                ->where('property_id', $property->id)
                ->first();

            if ($original === null) {
                throw new \DomainException('Charge not found on this folio.');
            }

            if ((int) $original->amount <= 0) {
                throw new \DomainException('Cannot reverse a non-positive or reversal charge.');
            }

            return (int) DB::table('folio_charges')->insertGetId([
                'folio_id' => $folioId,
                'property_id' => $property->id,
                'amount' => -((int) $original->amount),
                'description' => 'Reversal: '.$original->description,
                'item_type' => $original->item_type,
                'reversal_of_id' => $chargeId,
                'reason' => $reason,
                'posted_by' => $actorId,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Post a payment with optional idempotency key.
     *
     * @param  array{amount: int, tender_type: string, idempotency_key?: string|null, actor_id: int}  $data
     */
    public function postPayment(int $folioId, Property $property, array $data): int
    {
        return DB::transaction(function () use ($folioId, $property, $data): int {
            $this->guardFolio($folioId, $property);

            if (! empty($data['idempotency_key'])) {
                $existing = DB::table('folio_payments')
                    ->where('property_id', $property->id)
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();

                if ($existing !== null) {
                    return (int) $existing->id;
                }
            }

            return (int) DB::table('folio_payments')->insertGetId([
                'folio_id' => $folioId,
                'property_id' => $property->id,
                'amount' => $data['amount'],
                'tender_type' => $data['tender_type'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'refund_of_id' => null,
                'reason' => null,
                'posted_by' => $data['actor_id'],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Refund a previous payment with a compensating negative record.
     */
    public function refundPayment(int $folioId, Property $property, int $paymentId, string $reason, int $actorId): int
    {
        return DB::transaction(function () use ($folioId, $property, $paymentId, $reason, $actorId): int {
            $this->guardFolio($folioId, $property);

            $original = DB::table('folio_payments')
                ->where('id', $paymentId)
                ->where('folio_id', $folioId)
                ->where('property_id', $property->id)
                ->first();

            if ($original === null) {
                throw new \DomainException('Payment not found on this folio.');
            }

            if ((int) $original->amount <= 0) {
                throw new \DomainException('Cannot refund a non-positive or refund payment.');
            }

            return (int) DB::table('folio_payments')->insertGetId([
                'folio_id' => $folioId,
                'property_id' => $property->id,
                'amount' => -((int) $original->amount),
                'tender_type' => $original->tender_type,
                'idempotency_key' => null,
                'refund_of_id' => $paymentId,
                'reason' => $reason,
                'posted_by' => $actorId,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Calculate ledger balances.
     *
     * @return array{total_charges: int, total_payments: int, balance: int}
     */
    public function calculateBalance(int $folioId, Property $property): array
    {
        $this->guardFolio($folioId, $property);

        $totalCharges = (int) DB::table('folio_charges')
            ->where('folio_id', $folioId)
            ->where('property_id', $property->id)
            ->sum('amount');

        $totalPayments = (int) DB::table('folio_payments')
            ->where('folio_id', $folioId)
            ->where('property_id', $property->id)
            ->sum('amount');

        return [
            'total_charges' => $totalCharges,
            'total_payments' => $totalPayments,
            'balance' => $totalCharges - $totalPayments,
        ];
    }

    /**
     * Issue an immutable invoice snapshot.
     */
    public function issueInvoice(int $folioId, Property $property, int $actorId): int
    {
        return DB::transaction(function () use ($folioId, $property, $actorId): int {
            $this->guardFolio($folioId, $property);

            $balance = $this->calculateBalance($folioId, $property);

            $charges = DB::table('folio_charges')
                ->where('folio_id', $folioId)
                ->where('property_id', $property->id)
                ->orderBy('id')
                ->get();

            $payments = DB::table('folio_payments')
                ->where('folio_id', $folioId)
                ->where('property_id', $property->id)
                ->orderBy('id')
                ->get();

            $lineItems = $charges->map(function ($c) {
                return [
                    'id' => (int) $c->id,
                    'description' => $c->description,
                    'amount' => (int) $c->amount,
                    'item_type' => $c->item_type,
                ];
            })->all();

            $invoiceNumber = sprintf('INV-%s-%s-%04d', $property->id, date('Ymd'), $folioId);

            return (int) DB::table('invoices')->insertGetId([
                'property_id' => $property->id,
                'folio_id' => $folioId,
                'invoice_number' => $invoiceNumber,
                'total_charges' => $balance['total_charges'],
                'total_payments' => $balance['total_payments'],
                'balance_due' => $balance['balance'],
                'line_items_snapshot' => json_encode($lineItems, JSON_THROW_ON_ERROR),
                'issued_by' => $actorId,
                'created_at' => now(),
            ]);
        });
    }

    private function guardFolio(int $folioId, Property $property): void
    {
        $folio = DB::table('folios')
            ->where('id', $folioId)
            ->where('property_id', $property->id)
            ->first();

        if ($folio === null) {
            throw new \DomainException('Folio not found in this property.');
        }

        if ($folio->status !== 'open') {
            throw new \DomainException('Folio is not open (status: '.$folio->status.').');
        }
    }
}
