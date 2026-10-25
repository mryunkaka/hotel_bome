<?php

namespace App\Services\Finance;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * NightAuditService handles business date tracking, cashier shifts,
 * end-of-day closing snapshots, and audit-protected reopen workflows.
 *
 * Implements provisional D-010 decisions:
 * - Property operational business date (not simple calendar midnight).
 * - Cashier shift closure with counted vs expected reconciliation.
 * - Night audit close requires all shifts closed, generates immutable snapshot,
 *   and advances business date.
 * - Reopen requires explicit reason, records audit event, and cannot delete history.
 */
class NightAuditService
{
    /**
     * Get the active business date for the property, initializing to today if absent.
     */
    public function getCurrentBusinessDate(Property $property): Carbon
    {
        $row = DB::table('business_dates')
            ->where('property_id', $property->id)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        if ($row !== null) {
            return Carbon::parse($row->business_date);
        }

        $today = Carbon::today();

        DB::table('business_dates')->insert([
            'property_id' => $property->id,
            'business_date' => $today->toDateString(),
            'status' => 'open',
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $today;
    }

    /**
     * Open a cashier shift.
     */
    public function openShift(Property $property, int $cashierId, int $openingCash): int
    {
        $currentDate = $this->getCurrentBusinessDate($property);

        return (int) DB::table('cashier_shifts')->insertGetId([
            'property_id' => $property->id,
            'cashier_id' => $cashierId,
            'business_date' => $currentDate->toDateString(),
            'status' => 'open',
            'opening_cash' => $openingCash,
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Close a cashier shift with counted cash reconciliation.
     *
     * @return array{status: string, closing_cash_counted: int, closing_cash_expected: int, variance: int}
     */
    public function closeShift(int $shiftId, Property $property, int $countedCash, int $actorId): array
    {
        return DB::transaction(function () use ($shiftId, $property, $countedCash): array {
            $shift = DB::table('cashier_shifts')
                ->where('id', $shiftId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($shift === null) {
                throw new \DomainException('Cashier shift not found in this property.');
            }

            if ($shift->status !== 'open') {
                throw new \DomainException('Cashier shift is not open.');
            }

            // Expected cash = opening cash + cash payments posted by this cashier on this business date
            $cashPayments = (int) DB::table('folio_payments')
                ->where('property_id', $property->id)
                ->where('posted_by', $shift->cashier_id)
                ->where('tender_type', 'cash')
                ->whereDate('created_at', $shift->business_date)
                ->sum('amount');

            $expected = (int) $shift->opening_cash + $cashPayments;
            $variance = $countedCash - $expected;

            DB::table('cashier_shifts')
                ->where('id', $shiftId)
                ->update([
                    'status' => 'closed',
                    'closing_cash_counted' => $countedCash,
                    'closing_cash_expected' => $expected,
                    'variance' => $variance,
                    'closed_at' => now(),
                    'updated_at' => now(),
                ]);

            return [
                'status' => 'closed',
                'closing_cash_counted' => $countedCash,
                'closing_cash_expected' => $expected,
                'variance' => $variance,
            ];
        });
    }

    /**
     * Run Night Audit Day Close:
     * - Validates no cashier shifts are still open.
     * - Creates immutable closing snapshot.
     * - Marks current business date as closed.
     * - Opens the next business date (current + 1 day).
     */
    public function runNightAuditClose(Property $property, int $actorId): int
    {
        return DB::transaction(function () use ($property, $actorId): int {
            $currentDate = $this->getCurrentBusinessDate($property);
            $dateString = $currentDate->toDateString();

            // Guard: no open cashier shifts
            $openShiftsCount = DB::table('cashier_shifts')
                ->where('property_id', $property->id)
                ->where('business_date', $dateString)
                ->where('status', 'open')
                ->count();

            if ($openShiftsCount > 0) {
                throw new \DomainException("Cannot close business date: {$openShiftsCount} open cashier shifts remain.");
            }

            // Aggregate metrics for closing snapshot
            $totalCharges = (int) DB::table('folio_charges')
                ->where('property_id', $property->id)
                ->whereDate('created_at', $dateString)
                ->sum('amount');

            $totalPayments = (int) DB::table('folio_payments')
                ->where('property_id', $property->id)
                ->whereDate('created_at', $dateString)
                ->sum('amount');

            $activeStaysCount = DB::table('stays')
                ->where('property_id', $property->id)
                ->where('status', 'in_house')
                ->count();

            $activeReservationsCount = DB::table('reservations')
                ->where('property_id', $property->id)
                ->where('status', 'confirmed')
                ->count();

            $snapshotPayload = [
                'business_date' => $dateString,
                'total_charges' => $totalCharges,
                'total_payments' => $totalPayments,
                'active_stays_count' => $activeStaysCount,
                'active_reservations_count' => $activeReservationsCount,
                'closed_by' => $actorId,
                'closed_at' => now()->toIso8601String(),
            ];

            $snapshotId = (int) DB::table('day_closing_snapshots')->insertGetId([
                'property_id' => $property->id,
                'business_date' => $dateString,
                'total_charges' => $totalCharges,
                'total_payments' => $totalPayments,
                'active_stays_count' => $activeStaysCount,
                'active_reservations_count' => $activeReservationsCount,
                'snapshot_data' => json_encode($snapshotPayload, JSON_THROW_ON_ERROR),
                'closed_by' => $actorId,
                'closed_at' => now(),
            ]);

            // Close current business date
            DB::table('business_dates')
                ->where('property_id', $property->id)
                ->where('business_date', $dateString)
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_by' => $actorId,
                    'updated_at' => now(),
                ]);

            // Advance to next business date
            $nextDate = $currentDate->copy()->addDay();
            DB::table('business_dates')->insert([
                'property_id' => $property->id,
                'business_date' => $nextDate->toDateString(),
                'status' => 'open',
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $snapshotId;
        });
    }

    /**
     * Reopen an audit-closed business date with a required reason.
     */
    public function reopenBusinessDate(Property $property, string $businessDate, string $reason, int $actorId): void
    {
        DB::transaction(function () use ($property, $businessDate, $reason, $actorId): void {
            $row = DB::table('business_dates')
                ->where('property_id', $property->id)
                ->where('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new \DomainException('Business date not found in this property.');
            }

            if ($row->status !== 'closed') {
                throw new \DomainException('Business date is not closed.');
            }

            DB::table('business_dates')
                ->where('property_id', $property->id)
                ->where('business_date', $businessDate)
                ->update([
                    'status' => 'open',
                    'closed_at' => null,
                    'closed_by' => null,
                    'updated_at' => now(),
                ]);

            DB::table('business_date_reopens')->insert([
                'property_id' => $property->id,
                'business_date' => $businessDate,
                'reason' => $reason,
                'reopened_by' => $actorId,
                'created_at' => now(),
            ]);
        });
    }
}
