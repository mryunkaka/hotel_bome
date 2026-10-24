<?php

namespace App\Http\Controllers\Properties;

use App\Models\Property;
use App\Services\Properties\HousekeepingTasks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HousekeepingTaskController
{
    public function index(Request $request, Property $property): View
    {
        abort_unless($property->is_active, 404);
        $status = $request->validate(['status' => ['sometimes', 'nullable', 'in:open,in_progress,completed,cancelled']])['status'] ?? null;
        $tasks = DB::table('housekeeping_tasks as tasks')
            ->join('rooms', function ($join): void {
                $join->on('rooms.id', '=', 'tasks.room_id')->on('rooms.property_id', '=', 'tasks.property_id');
            })
            ->leftJoin('users as assignees', 'assignees.id', '=', 'tasks.assigned_to')
            ->where('tasks.property_id', $property->id)
            ->select(['tasks.id', 'tasks.property_id', 'tasks.room_id', 'tasks.assigned_to', 'tasks.status', 'tasks.instruction', 'tasks.created_at', 'rooms.code', 'assignees.name'])
            ->when($status !== null, fn ($query) => $query->where('tasks.status', $status))
            ->orderByDesc('tasks.created_at')
            ->paginate(20)
            ->withQueryString();
        $eventsByTask = DB::table('housekeeping_task_events as events')
            ->join('users', 'users.id', '=', 'events.actor_id')
            ->where('events.property_id', $property->id)
            ->whereIn('events.task_id', $tasks->getCollection()->pluck('id'))
            ->orderBy('events.id')
            ->get(['events.*', 'users.name as actor_name'])
            ->groupBy('task_id');
        $rooms = DB::table('rooms')->where('property_id', $property->id)->where('is_active', true)->orderBy('code')->get(['id', 'code']);
        $staff = DB::table('property_user')
            ->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $property->id)
            ->where('property_user.role', 'housekeeping')
            ->where('users.status', 'active')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        return view('properties.housekeeping.tasks', compact('property', 'tasks', 'rooms', 'staff', 'eventsByTask'));
    }

    public function store(Request $request, Property $property, HousekeepingTasks $service): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'instruction' => ['nullable', 'string', 'max:500'],
        ]);
        $service->create($property->id, (int) $validated['room_id'], isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null, $validated['instruction'] ?? null, $request->user());

        return back()->with('status', 'Tugas housekeeping dibuat.');
    }

    public function assign(Request $request, Property $property, int $task, HousekeepingTasks $service): RedirectResponse
    {
        $validated = $request->validate(['assigned_to' => ['nullable', 'integer']]);
        $service->assign($property->id, $task, isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null, $request->user());

        return back()->with('status', 'Penugasan diperbarui.');
    }

    public function transition(Request $request, Property $property, int $task, string $action, HousekeepingTasks $service): RedirectResponse
    {
        abort_unless(in_array($action, ['start', 'complete', 'cancel'], true), 404);
        $validated = $request->validate(['reason' => [$action === 'cancel' ? 'required' : 'nullable', 'string', 'max:500']]);
        $to = match ($action) {
            'start' => 'in_progress',
            'complete' => 'completed',
            'cancel' => 'cancelled',
        };
        $service->transition($property->id, $task, $to, $validated['reason'] ?? null, $request->user());

        return back()->with('status', 'Status tugas diperbarui.');
    }
}
