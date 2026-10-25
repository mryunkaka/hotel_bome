<?php

namespace App\Services\Connectors;

use App\Models\Property;
use App\Services\Finance\FolioService;
use Illuminate\Support\Facades\DB;

/**
 * PaymentWebhookHandler processes incoming external payment webhooks,
 * enforcing idempotency and automatically updating guest folios via FolioService.
 */
class PaymentWebhookHandler
{
    public function __construct(
        private readonly FolioService $folioService = new FolioService,
    ) {}

    /**
     * Handle incoming payment notification webhook.
     *
     * @param  array{
     *     event: string,
     *     property_id: int,
     *     folio_id: int,
     *     amount: int,
     *     idempotency_key: string,
     *     tender_type?: string
     * }  $payload
     * @return array{status: string, payment_id: int, amount: int}
     */
    public function handleWebhook(Property $property, array $payload): array
    {
        return DB::transaction(function () use ($property, $payload): array {
            $folioId = (int) $payload['folio_id'];
            $amount = (int) $payload['amount'];
            $idempotencyKey = $payload['idempotency_key'];
            $tenderType = $payload['tender_type'] ?? 'card';

            // Check if payment already exists with this idempotency key in this property
            $existing = DB::table('folio_payments')
                ->where('property_id', $property->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return [
                    'status' => 'reconciled_duplicate',
                    'payment_id' => (int) $existing->id,
                    'amount' => (int) $existing->amount,
                ];
            }

            // Post payment to folio using FolioService
            $paymentId = $this->folioService->postPayment($folioId, $property, [
                'amount' => $amount,
                'tender_type' => $tenderType,
                'idempotency_key' => $idempotencyKey,
                'actor_id' => 1, // System gateway webhook actor
            ]);

            return [
                'status' => 'processed',
                'payment_id' => $paymentId,
                'amount' => $amount,
            ];
        });
    }
}
