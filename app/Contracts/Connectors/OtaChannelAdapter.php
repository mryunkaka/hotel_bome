<?php

namespace App\Contracts\Connectors;

use App\Models\Property;

interface OtaChannelAdapter
{
    /**
     * Sync inventory/availability updates to remote OTA channels.
     *
     * @param  array<string, mixed>  $inventoryUpdates
     * @return array<string, mixed>
     */
    public function syncInventory(Property $property, array $inventoryUpdates): array;

    /**
     * Import external incoming bookings from OTA channels.
     *
     * @return array<string, mixed>
     */
    public function importBookings(Property $property): array;
}
