<?php

namespace App\Services\Properties;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkOrders
{
    public function create(int $propertyId, ?int $roomId, ?int $assigneeId, string $title, ?string $description, User $actor, ?int $assetId = null): int
    {
        return DB::transaction(function () use ($propertyId, $roomId, $assigneeId, $title, $description, $actor, $assetId): int {
            $this->assertProperty($propertyId);
            $this->assertActor($propertyId, $actor);
            if ($roomId !== null) {
                abort_unless(DB::table('rooms')->where('property_id', $propertyId)->where('id', $roomId)->where('is_active', true)->exists(), 404);
            }
            if ($assigneeId !== null) {
                $this->assertAssignee($propertyId, $assigneeId);
            }
            if ($assetId !== null) {
                abort_unless(DB::table('engineering_assets')->where('property_id', $propertyId)->where('id', $assetId)->where('is_active', true)->exists(), 404);
            }
            $now = now();
            $id = DB::table('work_orders')->insertGetId([
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'asset_id' => $assetId,
                'assigned_to' => $assigneeId,
                'created_by' => $actor->id,
                'status' => 'open',
                'title' => $title,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->event($propertyId, $id, 'created', null, 'open', $actor, null, $assigneeId);

            return $id;
        });
    }

    public function assign(int $propertyId, int $id, ?int $assigneeId, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $id, $assigneeId, $actor): void {
            $this->assertProperty($propertyId);
            $this->assertActor($propertyId, $actor);
            $order = $this->order($propertyId, $id, true);
            if ($order->status !== 'open') {
                throw ValidationException::withMessages(['status' => 'Only open work orders can be reassigned.']);
            }
            if ($assigneeId !== null) {
                $this->assertAssignee($propertyId, $assigneeId);
            }
            DB::table('work_orders')->where('property_id', $propertyId)->where('id', $id)->update(['assigned_to' => $assigneeId, 'updated_at' => now()]);
            $this->event($propertyId, $id, 'assigned', null, null, $actor, $order->assigned_to, $assigneeId);
        });
    }

    public function transition(int $propertyId, int $id, string $to, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $id, $to, $reason, $actor): void {
            $this->assertProperty($propertyId);
            $this->assertActor($propertyId, $actor);
            $order = $this->order($propertyId, $id, true);
            $allowed = match ($to) {
                'in_progress' => $order->status === 'open',
                'resolved' => $order->status === 'in_progress',
                'cancelled' => in_array($order->status, ['open', 'in_progress'], true) && trim((string) $reason) !== '',
                default => false,
            };
            if (! $allowed) {
                throw ValidationException::withMessages(['status' => 'Invalid work order transition.']);
            }
            DB::table('work_orders')->where('property_id', $propertyId)->where('id', $id)->update(['status' => $to, 'updated_at' => now()]);
            $this->event($propertyId, $id, 'status_changed', $order->status, $to, $actor, $order->assigned_to, $order->assigned_to, $reason);
        });
    }

    /** @return object{status: string, assigned_to: int|null, room_id: int|null, asset_id: int|null, created_by: int} */
    public function order(int $propertyId, int $id, bool $lock = false): object
    {
        $query = DB::table('work_orders')->where('property_id', $propertyId)->where('id', $id);
        if ($lock) {
            $query->lockForUpdate();
        }
        $order = $query->first();
        if ($order === null) {
            throw new NotFoundHttpException;
        }

        /** @var object{status: string, assigned_to: int|null, room_id: int|null, asset_id: int|null, created_by: int} $order */
        return $order;
    }

    private function assertActor(int $propertyId, User $actor): void
    {
        if ($actor->is_platform_admin) {
            return;
        }
        $allowed = DB::table('property_user')->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $propertyId)->where('property_user.user_id', $actor->id)
            ->where('property_user.role', 'operations')->where('users.status', 'active')->exists();
        abort_unless($allowed, 404);
    }

    private function assertProperty(int $propertyId): void
    {
        abort_unless(DB::table('properties')->where('id', $propertyId)->where('is_active', true)->exists(), 404);
    }

    private function assertAssignee(int $propertyId, int $userId): void
    {
        $allowed = DB::table('property_user')->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $propertyId)->where('property_user.user_id', $userId)
            ->where('property_user.role', 'operations')->where('users.status', 'active')->exists();
        abort_unless($allowed, 404);
    }

    private function event(int $propertyId, int $id, string $type, ?string $from, ?string $to, User $actor, ?int $before, ?int $after, ?string $reason = null): void
    {
        DB::table('work_order_events')->insert([
            'property_id' => $propertyId, 'work_order_id' => $id, 'event_type' => $type,
            'from_status' => $from, 'to_status' => $to, 'actor_id' => $actor->id,
            'assigned_to_before' => $before, 'assigned_to_after' => $after,
            'reason' => $reason, 'created_at' => now(),
        ]);
    }
}
