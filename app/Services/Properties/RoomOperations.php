<?php

namespace App\Services\Properties;

use App\Enums\HousekeepingStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RoomOperations
{
    public function changeHousekeepingStatus(int $propertyId, int $roomId, HousekeepingStatus $next, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($propertyId, $roomId, $next, $actor, $reason): void {
            $this->lockActiveRoom($propertyId, $roomId);
            $lastEvent = DB::table('room_status_events')
                ->where('property_id', $propertyId)
                ->where('room_id', $roomId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($lastEvent === null) {
                throw new ModelNotFoundException('Room status history not found.');
            }

            $current = HousekeepingStatus::from($lastEvent->status);
            if (! $current->canTransitionTo($next)) {
                throw new \DomainException("Invalid housekeeping transition from {$current->value} to {$next->value}.");
            }

            DB::table('room_status_events')->insert([
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'previous_status' => $current->value,
                'status' => $next->value,
                'actor_id' => $actor->id,
                'reason' => $this->optionalReason($reason),
                'created_at' => now(),
            ]);
        });
    }

    public function createBlock(int $propertyId, int $roomId, CarbonImmutable $startDate, CarbonImmutable $endDate, string $reason, User $actor): int
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500 || $startDate->gt($endDate)) {
            throw new \InvalidArgumentException('Room block requires valid inclusive dates and a reason up to 500 characters.');
        }

        return DB::transaction(function () use ($propertyId, $roomId, $startDate, $endDate, $reason, $actor): int {
            $this->lockActiveRoom($propertyId, $roomId);

            return (int) DB::table('room_blocks')->insertGetId([
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'reason' => $reason,
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);
        });
    }

    public function cancelBlock(int $propertyId, int $roomId, int $blockId, string $reason, User $actor): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new \InvalidArgumentException('Cancellation requires a reason up to 500 characters.');
        }

        DB::transaction(function () use ($propertyId, $roomId, $blockId, $reason, $actor): void {
            $this->lockActiveRoom($propertyId, $roomId);
            $block = DB::table('room_blocks')
                ->where('property_id', $propertyId)
                ->where('room_id', $roomId)
                ->where('id', $blockId)
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('room_block_cancellations')
                        ->whereColumn('room_block_cancellations.room_block_id', 'room_blocks.id');
                })
                ->lockForUpdate()
                ->first();
            if ($block === null) {
                throw new ModelNotFoundException('Room block not found.');
            }

            DB::table('room_block_cancellations')->insert([
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'room_block_id' => $blockId,
                'actor_id' => $actor->id,
                'created_at' => now(),
                'reason' => $reason,
            ]);
        });
    }

    public function hasActiveBlockOnDate(int $propertyId, int $roomId, CarbonImmutable $date): bool
    {
        return DB::table('room_blocks')
            ->where('property_id', $propertyId)
            ->where('room_id', $roomId)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('room_block_cancellations')
                    ->whereColumn('room_block_cancellations.room_block_id', 'room_blocks.id');
            })
            ->exists();
    }

    private function lockActiveRoom(int $propertyId, int $roomId): void
    {
        $room = DB::table('rooms')
            ->join('properties', 'rooms.property_id', '=', 'properties.id')
            ->where('rooms.property_id', $propertyId)
            ->where('rooms.id', $roomId)
            ->where('rooms.is_active', true)
            ->where('properties.is_active', true)
            ->select('rooms.id')
            ->lockForUpdate()
            ->first();
        if ($room === null) {
            throw new ModelNotFoundException('Active room not found.');
        }
    }

    private function optionalReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : mb_substr($reason, 0, 500);
    }
}
