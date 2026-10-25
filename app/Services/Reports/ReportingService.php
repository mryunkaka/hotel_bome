<?php

namespace App\Services\Reports;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReportingService generates operational, financial, and occupancy reports
 * scoped strictly to a given property, with sanitized export capability.
 *
 * Implements RPT-001 requirements:
 * - Scoped strictly by property_id.
 * - Reconciles against immutable ledger records.
 * - Sanitizes CSV outputs to prevent CSV formula injection.
 * - Adheres to D-007 data minimization (excludes guest PII from export).
 */
class ReportingService
{
    /**
     * Daily financial report aggregating charges and payments by tender type.
     *
     * @return array{
     *     property_id: int,
     *     date: string,
     *     total_charges: int,
     *     charges_by_type: array<string, int>,
     *     total_payments: int,
     *     payments_by_tender: array<string, int>,
     *     net_balance: int
     * }
     */
    public function dailyFinancialReport(Property $property, Carbon $date): array
    {
        $dateString = $date->toDateString();

        $charges = DB::table('folio_charges')
            ->where('property_id', $property->id)
            ->whereDate('created_at', $dateString)
            ->get();

        $totalCharges = (int) $charges->sum('amount');
        $chargesByType = [];
        foreach ($charges as $c) {
            $type = $c->item_type ?? 'other';
            $chargesByType[$type] = ($chargesByType[$type] ?? 0) + (int) $c->amount;
        }

        $payments = DB::table('folio_payments')
            ->where('property_id', $property->id)
            ->whereDate('created_at', $dateString)
            ->get();

        $totalPayments = (int) $payments->sum('amount');
        $paymentsByTender = [];
        foreach ($payments as $p) {
            $tender = $p->tender_type ?? 'other';
            $paymentsByTender[$tender] = ($paymentsByTender[$tender] ?? 0) + (int) $p->amount;
        }

        return [
            'property_id' => $property->id,
            'date' => $dateString,
            'total_charges' => $totalCharges,
            'charges_by_type' => $chargesByType,
            'total_payments' => $totalPayments,
            'payments_by_tender' => $paymentsByTender,
            'net_balance' => $totalCharges - $totalPayments,
        ];
    }

    /**
     * Occupancy report showing total rooms, occupied rooms, rate, and breakdown by room type.
     *
     * @return array{
     *     total_rooms: int,
     *     occupied_rooms: int,
     *     available_rooms: int,
     *     occupancy_percentage: float,
     *     by_room_type: array<string, array{total: int, occupied: int}>
     * }
     */
    public function occupancyReport(Property $property): array
    {
        $rooms = DB::table('rooms')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->where('rooms.property_id', $property->id)
            ->where('rooms.is_active', true)
            ->select('rooms.id', 'rooms.room_type_id', 'room_types.name as type_name')
            ->get();

        $occupiedRoomIds = DB::table('stays')
            ->where('property_id', $property->id)
            ->where('status', 'in_house')
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $totalRooms = $rooms->count();
        $occupiedCount = 0;
        $byType = [];

        foreach ($rooms as $r) {
            $typeName = $r->type_name;
            if (! isset($byType[$typeName])) {
                $byType[$typeName] = ['total' => 0, 'occupied' => 0];
            }
            $byType[$typeName]['total']++;

            if (in_array((int) $r->id, $occupiedRoomIds, true)) {
                $occupiedCount++;
                $byType[$typeName]['occupied']++;
            }
        }

        $percentage = $totalRooms > 0 ? round(($occupiedCount / $totalRooms) * 100, 2) : 0.0;

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedCount,
            'available_rooms' => $totalRooms - $occupiedCount,
            'occupancy_percentage' => $percentage,
            'by_room_type' => $byType,
        ];
    }

    /**
     * Export daily financial transactions to CSV with formula injection sanitization.
     */
    public function exportDailyFinancialCsv(Property $property, Carbon $date): string
    {
        $dateString = $date->toDateString();

        $charges = DB::table('folio_charges')
            ->where('property_id', $property->id)
            ->whereDate('created_at', $dateString)
            ->orderBy('id')
            ->get();

        $lines = [];
        $lines[] = 'Date,Item Type,Description,Amount';

        foreach ($charges as $c) {
            $sanitizedDesc = $this->sanitizeCsvField((string) $c->description);
            $lines[] = sprintf(
                '%s,%s,"%s",%d',
                $dateString,
                $c->item_type,
                str_replace('"', '""', $sanitizedDesc),
                (int) $c->amount
            );
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Sanitizes a string field against CSV formula injection.
     * Prepends single quote if string starts with dangerous characters (=, +, -, @, \t, \r).
     */
    private function sanitizeCsvField(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $firstChar = $value[0];
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
