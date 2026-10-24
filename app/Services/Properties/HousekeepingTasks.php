<?php

namespace App\Services\Properties;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HousekeepingTasks
{
    public function create(int $propertyId, int $roomId, ?int $assigneeId, ?string $instruction, User $actor): int
    {
        return DB::transaction(function () use ($propertyId, $roomId, $assigneeId, $instruction, $actor): int {
            $this->assertActiveProperty($propertyId);
            $this->assertActiveRoom($propertyId, $roomId);
            if ($assigneeId !== null) {
                $this->assertEligibleAssignee($propertyId, $assigneeId);
            }

            $now = now();
            $taskId = DB::table('housekeeping_tasks')->insertGetId([
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'assigned_to' => $assigneeId,
                'created_by' => $actor->id,
                'status' => 'open',
                'instruction' => $instruction,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->event($propertyId, $taskId, 'created', null, 'open', $actor, null, $assigneeId);

            return $taskId;
        });
    }

    public function assign(int $propertyId, int $taskId, ?int $assigneeId, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $taskId, $assigneeId, $actor): void {
            $this->assertActiveProperty($propertyId);
            $task = $this->task($propertyId, $taskId, true);
            if ($task->status !== 'open') {
                throw ValidationException::withMessages(['status' => 'Only open tasks can be reassigned.']);
            }
            if ($assigneeId !== null) {
                $this->assertEligibleAssignee($propertyId, $assigneeId);
            }
            if ((int) ($task->assigned_to ?? 0) === (int) ($assigneeId ?? 0)) {
                return;
            }

            DB::table('housekeeping_tasks')->where('property_id', $propertyId)->where('id', $taskId)->update([
                'assigned_to' => $assigneeId,
                'updated_at' => now(),
            ]);
            $this->event($propertyId, $taskId, 'assigned', null, null, $actor, $task->assigned_to, $assigneeId);
        });
    }

    public function transition(int $propertyId, int $taskId, string $to, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $taskId, $to, $reason, $actor): void {
            $this->assertActiveProperty($propertyId);
            $task = $this->task($propertyId, $taskId, true);
            $valid = match ($to) {
                'in_progress' => $task->status === 'open',
                'completed' => $task->status === 'in_progress',
                'cancelled' => in_array($task->status, ['open', 'in_progress'], true) && trim((string) $reason) !== '',
                default => false,
            };
            if (! $valid) {
                throw ValidationException::withMessages(['status' => 'Invalid housekeeping task transition.']);
            }

            DB::table('housekeeping_tasks')->where('property_id', $propertyId)->where('id', $taskId)->update([
                'status' => $to,
                'updated_at' => now(),
            ]);
            $this->event($propertyId, $taskId, 'status_changed', $task->status, $to, $actor, $task->assigned_to, $task->assigned_to, $reason);
        });
    }

    /** @return object{status: string, assigned_to: int|null} */
    public function task(int $propertyId, int $taskId, bool $lock = false): object
    {
        $query = DB::table('housekeeping_tasks')
            ->where('property_id', $propertyId)
            ->where('id', $taskId);
        if ($lock) {
            $query->lockForUpdate();
        }
        $task = $query->first();
        if ($task === null) {
            throw new NotFoundHttpException;
        }

        /** @var object{status: string, assigned_to: int|null} $task */
        return $task;
    }

    public function assertActiveProperty(int $propertyId): void
    {
        abort_unless(DB::table('properties')->where('id', $propertyId)->where('is_active', true)->exists(), 404);
    }

    private function assertActiveRoom(int $propertyId, int $roomId): void
    {
        abort_unless(DB::table('rooms')->where('property_id', $propertyId)->where('id', $roomId)->where('is_active', true)->exists(), 404);
    }

    private function assertEligibleAssignee(int $propertyId, int $userId): void
    {
        $eligible = DB::table('property_user')
            ->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $propertyId)
            ->where('property_user.user_id', $userId)
            ->where('property_user.role', 'housekeeping')
            ->where('users.status', 'active')
            ->exists();
        abort_unless($eligible, 404);
    }

    private function event(int $propertyId, int $taskId, string $type, ?string $from, ?string $to, User $actor, ?int $assigneeBefore, ?int $assigneeAfter, ?string $reason = null): void
    {
        DB::table('housekeeping_task_events')->insert([
            'property_id' => $propertyId,
            'task_id' => $taskId,
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'assigned_to_before' => $assigneeBefore,
            'assigned_to_after' => $assigneeAfter,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
