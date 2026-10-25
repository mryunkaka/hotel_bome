<?php

namespace App\Http\Controllers\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserMembershipController
{
    /** @var list<string> */
    private const ROLES = ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations', 'receptionist', 'supervisor', 'owner'];

    public function index(Request $request): View
    {
        $users = User::query()
            ->where('id', '<>', $request->user()->id)
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'active')->where('is_platform_admin', false);
                })->orWhereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'users.id');
                });
            })
            ->orderBy('name')
            ->paginate(25);
        $memberships = DB::table('property_user')
            ->join('properties', 'properties.id', '=', 'property_user.property_id')
            ->whereIn('property_user.user_id', $users->pluck('id'))
            ->orderBy('properties.name')
            ->get([
                'property_user.user_id',
                'property_user.property_id',
                'property_user.role',
                'properties.name as property_name',
                'properties.code as property_code',
                'properties.is_active as property_is_active',
            ])
            ->groupBy('user_id');

        $events = DB::table('property_membership_events')
            ->join('users as actors', 'actors.id', '=', 'property_membership_events.actor_id')
            ->join('users as targets', 'targets.id', '=', 'property_membership_events.user_id')
            ->join('properties', 'properties.id', '=', 'property_membership_events.property_id')
            ->orderByDesc('property_membership_events.id')
            ->limit(100)
            ->get([
                'property_membership_events.event',
                'property_membership_events.previous_role',
                'property_membership_events.role',
                'property_membership_events.reason',
                'property_membership_events.created_at',
                'actors.name as actor_name',
                'targets.name as target_name',
                'properties.name as property_name',
            ]);

        return view('identity.users.memberships', [
            'users' => $users,
            'memberships' => $memberships,
            'properties' => Property::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => self::ROLES,
            'events' => $events,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('is_active', true)],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
            'reason' => ['required', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        $event = DB::transaction(function () use ($request, $user, $data): string {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->id !== $request->user()->id, 404);
            abort_unless($target->status === 'active' && ! $target->is_platform_admin, 404);

            $property = Property::query()
                ->whereKey((int) $data['property_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if ($property === null) {
                throw ValidationException::withMessages(['property_id' => __('Select an active property.')]);
            }

            $membership = DB::table('property_user')
                ->where('user_id', $target->id)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();
            if ($membership?->role === $data['role']) {
                return 'Membership already has this role.';
            }

            if ($membership === null) {
                DB::table('property_user')->insert([
                    'user_id' => $target->id,
                    'property_id' => $property->id,
                    'role' => $data['role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $eventType = 'assigned';
                $previousRole = null;
            } else {
                DB::table('property_user')
                    ->where('user_id', $target->id)
                    ->where('property_id', $property->id)
                    ->update(['role' => $data['role'], 'updated_at' => now()]);
                $eventType = 'role_changed';
                $previousRole = $membership->role;
            }

            $target->setRememberToken(Str::random(60));
            $target->forceFill(['auth_version' => ((int) $target->auth_version) + 1])->save();
            $this->recordEvent($request, $target, $property, $eventType, $previousRole, $data['role'], $data['reason']);

            return $eventType === 'assigned' ? 'Membership assigned.' : 'Membership role changed.';
        });

        return redirect()->route('admin.users.memberships.index')->with('status', __($event));
    }

    public function destroy(Request $request, User $user, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $property, $data): void {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->id !== $request->user()->id, 404);
            $membership = DB::table('property_user')
                ->where('user_id', $target->id)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();
            abort_if($membership === null, 404);

            DB::table('property_user')
                ->where('user_id', $target->id)
                ->where('property_id', $property->id)
                ->delete();

            $target->setRememberToken(Str::random(60));
            $target->forceFill(['auth_version' => ((int) $target->auth_version) + 1])->save();
            $this->recordEvent($request, $target, $property, 'revoked', $membership->role, null, $data['reason']);
        });

        return redirect()->route('admin.users.memberships.index')->with('status', __('Membership revoked.'));
    }

    private function recordEvent(
        Request $request,
        User $target,
        Property $property,
        string $event,
        ?string $previousRole,
        ?string $role,
        string $reason,
    ): void {
        DB::table('property_membership_events')->insert([
            'actor_id' => $request->user()->id,
            'user_id' => $target->id,
            'property_id' => $property->id,
            'event' => $event,
            'previous_role' => $previousRole,
            'role' => $role,
            'reason' => trim($reason),
            'created_at' => now(),
        ]);
    }
}
