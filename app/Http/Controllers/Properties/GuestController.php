<?php

namespace App\Http\Controllers\Properties;

use App\Models\Guest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuestController
{
    public function properties(Request $request): View
    {
        abort_unless($request->user()->is_platform_admin || $request->user()->hasGuestOwnerAccess(), 404);

        return view('properties.guests.properties', [
            'properties' => Property::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function index(Request $request, Property $property): View
    {
        $this->recordAccess($request, $property, 'guest_list_viewed');
        $query = DB::table('guests')
            ->where('property_id', $property->id)
            ->select('id', 'display_name', 'notes', 'is_active', 'is_validated', 'created_at', 'updated_at');

        if ($search = $request->string('q')->trim()->value()) {
            abort_if(mb_strlen($search) > 100, 422);
            $query->where('display_name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%');
        }

        $filter = $request->string('status')->value();
        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filter !== '') {
            abort(422);
        }

        $pendingRequests = $this->roleFor($request, $property) === 'owner'
            ? DB::table('guest_deactivation_requests')
                ->join('guests', 'guests.id', '=', 'guest_deactivation_requests.guest_id')
                ->join('users', 'users.id', '=', 'guest_deactivation_requests.requester_id')
                ->where('guest_deactivation_requests.property_id', $property->id)
                ->where('guest_deactivation_requests.status', 'pending')
                ->orderBy('guest_deactivation_requests.id')
                ->get([
                    'guest_deactivation_requests.id',
                    'guest_deactivation_requests.guest_id',
                    'guest_deactivation_requests.reason',
                    'guests.display_name',
                    'users.name as requester_name',
                ])
            : collect();

        return view('properties.guests.index', [
            'property' => $property,
            'guests' => $query->orderBy('display_name')->simplePaginate(20)->withQueryString(),
            'pendingRequests' => $pendingRequests,
            'role' => $this->roleFor($request, $property),
        ]);
    }

    public function create(Property $property): View
    {
        return view('properties.guests.form', [
            'property' => $property,
            'guest' => new Guest,
            'requiresReason' => false,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($property, $data, $request): void {
            $guest = new Guest($data);
            $guest->property_id = $property->id;
            $guest->save();

            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'event_type' => 'created',
                'changes' => json_encode(['display_name', 'notes']),
                'reason' => null,
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            $this->recordAccess($request, $property, 'guest_created');
        });

        return redirect()->route('properties.guests.index', $property)->with('status', __('Guest created.'));
    }

    public function edit(Request $request, Property $property, Guest $guest): View
    {
        abort_unless((int) $guest->property_id === (int) $property->id, 404);
        $this->recordAccess($request, $property, 'guest_record_viewed');

        return view('properties.guests.form', [
            'property' => $property,
            'guest' => $guest,
            'requiresReason' => $this->roleFor($request, $property) === 'receptionist',
        ]);
    }

    public function update(Request $request, Property $property, Guest $guest): RedirectResponse
    {
        abort_unless((int) $guest->property_id === (int) $property->id, 404);
        $requiresReason = $this->roleFor($request, $property) === 'receptionist';
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reason' => [$requiresReason ? 'required' : 'nullable', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        DB::transaction(function () use ($guest, $property, $data, $request): void {
            $locked = Guest::query()->whereKey($guest->id)->where('property_id', $property->id)->lockForUpdate()->firstOrFail();
            $changedFields = [];
            if ($locked->display_name !== $data['display_name']) {
                $changedFields[] = 'display_name';
            }
            if (($locked->notes ?? '') !== ($data['notes'] ?? '')) {
                $changedFields[] = 'notes';
            }

            $locked->update(['display_name' => $data['display_name'], 'notes' => $data['notes'] ?? null]);

            if ($changedFields !== []) {
                DB::table('guest_events')->insert([
                    'property_id' => $property->id,
                    'guest_id' => $locked->id,
                    'event_type' => 'updated',
                    'changes' => json_encode($changedFields),
                    'reason' => isset($data['reason']) ? trim($data['reason']) : null,
                    'actor_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
                $this->recordAccess($request, $property, 'guest_updated');
            }
        });

        return redirect()->route('properties.guests.index', $property)->with('status', __('Guest updated.'));
    }

    public function deactivate(Request $request, Property $property, Guest $guest): RedirectResponse
    {
        abort_unless((int) $guest->property_id === (int) $property->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'regex:/\\S/', 'max:500']]);
        $role = $this->roleFor($request, $property);

        DB::transaction(function () use ($guest, $property, $request, $data, $role): void {
            $locked = Guest::query()->whereKey($guest->id)->where('property_id', $property->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->is_active, 404);

            if ($role === 'receptionist') {
                $pending = DB::table('guest_deactivation_requests')
                    ->where('guest_id', $locked->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();
                if ($pending) {
                    throw ValidationException::withMessages(['reason' => __('A deactivation request is already pending.')]);
                }
                DB::table('guest_deactivation_requests')->insert([
                    'property_id' => $property->id,
                    'guest_id' => $locked->id,
                    'requester_id' => $request->user()->id,
                    'reason' => trim($data['reason']),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('guest_events')->insert([
                    'property_id' => $property->id,
                    'guest_id' => $locked->id,
                    'event_type' => 'deactivation_requested',
                    'changes' => null,
                    'reason' => trim($data['reason']),
                    'actor_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
                $event = 'deactivation_requested';
            } else {
                $locked->forceFill(['is_active' => false])->save();
                DB::table('guest_events')->insert([
                    'property_id' => $property->id,
                    'guest_id' => $locked->id,
                    'event_type' => 'deactivated',
                    'changes' => null,
                    'reason' => trim($data['reason']),
                    'actor_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
                $event = 'guest_deactivated';
            }

            $this->recordAccess($request, $property, $event);
        });

        $message = $role === 'receptionist' ? __('Deactivation request submitted for owner approval.') : __('Guest deactivated.');

        return redirect()->route('properties.guests.index', $property)->with('status', $message);
    }

    public function approveDeactivation(Request $request, Property $property, int $requestId): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'regex:/\\S/', 'max:500']]);

        DB::transaction(function () use ($request, $property, $requestId, $data): void {
            $approval = DB::table('guest_deactivation_requests')
                ->where('id', $requestId)->where('property_id', $property->id)->lockForUpdate()->first();
            abort_unless($approval !== null && $approval->status === 'pending', 404);
            $guest = Guest::query()->whereKey($approval->guest_id)->where('property_id', $property->id)->lockForUpdate()->firstOrFail();
            abort_unless($guest->is_active, 404);

            $guest->forceFill(['is_active' => false])->save();
            DB::table('guest_deactivation_requests')->where('id', $approval->id)->update([
                'status' => 'approved',
                'approver_id' => $request->user()->id,
                'decision_reason' => trim($data['reason']),
                'decided_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'event_type' => 'deactivation_approved',
                'changes' => null,
                'reason' => trim($data['reason']),
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            $this->recordAccess($request, $property, 'deactivation_approved');
        });

        return redirect()->route('properties.guests.index', $property)->with('status', __('Deactivation approved.'));
    }

    public function rejectDeactivation(Request $request, Property $property, int $requestId): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'regex:/\\S/', 'max:500']]);

        DB::transaction(function () use ($request, $property, $requestId, $data): void {
            $approval = DB::table('guest_deactivation_requests')
                ->where('id', $requestId)->where('property_id', $property->id)->lockForUpdate()->first();
            abort_unless($approval !== null && $approval->status === 'pending', 404);
            DB::table('guest_deactivation_requests')->where('id', $approval->id)->update([
                'status' => 'rejected',
                'approver_id' => $request->user()->id,
                'decision_reason' => trim($data['reason']),
                'decided_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $approval->guest_id,
                'event_type' => 'deactivation_rejected',
                'changes' => null,
                'reason' => trim($data['reason']),
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            $this->recordAccess($request, $property, 'deactivation_rejected');
        });

        return redirect()->route('properties.guests.index', $property)->with('status', __('Deactivation rejected.'));
    }

    public function validateGuest(Request $request, Property $property, Guest $guest): RedirectResponse
    {
        abort_unless((int) $guest->property_id === (int) $property->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'regex:/\\S/', 'max:500']]);

        DB::transaction(function () use ($guest, $property, $request, $data): void {
            $locked = Guest::query()->whereKey($guest->id)->where('property_id', $property->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['is_validated' => true])->save();
            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'event_type' => 'validated',
                'changes' => json_encode(['is_validated']),
                'reason' => trim($data['reason']),
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            $this->recordAccess($request, $property, 'guest_validated');
        });

        return redirect()->route('properties.guests.index', $property)->with('status', __('Guest data marked as validated.'));
    }

    public function activity(Request $request, Property $property): View
    {
        $this->recordAccess($request, $property, 'guest_activity_viewed');
        $profileEvents = DB::table('guest_events')
            ->join('guests', 'guests.id', '=', 'guest_events.guest_id')
            ->join('users', 'users.id', '=', 'guest_events.actor_id')
            ->where('guest_events.property_id', $property->id)
            ->select([
                'guests.display_name as display_name',
                'guest_events.event_type as event_type',
                'guest_events.reason as reason',
                'users.name as actor_name',
                'guest_events.created_at as created_at',
            ]);
        $accessEvents = DB::table('guest_access_events')
            ->join('users', 'users.id', '=', 'guest_access_events.actor_id')
            ->where('guest_access_events.property_id', $property->id)
            ->selectRaw('NULL as display_name, guest_access_events.event as event_type, NULL as reason, users.name as actor_name, guest_access_events.created_at as created_at');
        $events = DB::query()
            ->fromSub($profileEvents->unionAll($accessEvents), 'guest_activity')
            ->orderByDesc('created_at')
            ->simplePaginate(50)
            ->withQueryString();

        return view('properties.guests.activity', compact('property', 'events'));
    }

    public function report(Request $request, Property $property): View
    {
        $this->recordAccess($request, $property, 'guest_report_viewed');
        $report = [
            'active' => DB::table('guests')->where('property_id', $property->id)->where('is_active', true)->count(),
            'inactive' => DB::table('guests')->where('property_id', $property->id)->where('is_active', false)->count(),
            'validated' => DB::table('guests')->where('property_id', $property->id)->where('is_validated', true)->count(),
            'pending_deactivation' => DB::table('guest_deactivation_requests')->where('property_id', $property->id)->where('status', 'pending')->count(),
        ];

        return view('properties.guests.report', compact('property', 'report'));
    }

    private function recordAccess(Request $request, Property $property, string $event): void
    {
        DB::table('guest_access_events')->insert([
            'property_id' => $property->id,
            'actor_id' => $request->user()->id,
            'event' => $event,
            'created_at' => now(),
        ]);
    }

    private function roleFor(Request $request, Property $property): ?string
    {
        if ($request->user()->is_platform_admin) {
            return 'platform_admin';
        }

        if ($request->user()->hasGuestOwnerAccess()) {
            return 'owner';
        }

        return DB::table('property_user')
            ->where('user_id', $request->user()->id)
            ->where('property_id', $property->id)
            ->value('role');
    }
}
