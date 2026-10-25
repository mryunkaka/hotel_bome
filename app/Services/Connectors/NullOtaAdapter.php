<?php

namespace App\Services\Connectors;

use App\Contracts\Connectors\OtaChannelAdapter;
use App\Models\Property;

/**
 * NullOtaAdapter is the safe, disabled-by-default fallback OTA adapter.
 * Ensures local core operation never crashes when no external provider is contracted (D-009).
 */
class NullOtaAdapter implements OtaChannelAdapter
{
    public function syncInventory(Property $property, array $inventoryUpdates): array
    {
        return [
            'status' => 'disabled',
            'is_active' => false,
            'message' => 'OTA connector is disabled by default per D-009 policy.',
        ];
    }

    public function importBookings(Property $property): array
    {
        return [
            'status' => 'disabled',
            'is_active' => false,
            'bookings' => [],
        ];
    }
}
