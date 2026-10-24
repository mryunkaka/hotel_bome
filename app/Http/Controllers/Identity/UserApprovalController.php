<?php

namespace App\Http\Controllers\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserApprovalController
{
    /** @var list<string> */
    private const ROLES = ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations'];

    public function index(): View
    {
        return view('identity.users.index', [
            'pendingUsers' => User::query()->where('status', 'pending')->orderBy('created_at')->paginate(25),
            'properties' => Property::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => self::ROLES,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('is_active', true)],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->status === 'pending', 404);

            $property = Property::query()
                ->whereKey((int) $data['property_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($property === null) {
                throw ValidationException::withMessages(['property_id' => __('Select an active property.')]);
            }

            DB::table('property_user')->insert([
                'user_id' => $target->id,
                'property_id' => $property->id,
                'role' => $data['role'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $target->forceFill(['status' => 'active'])->save();
            $this->recordEvent($request, $target, 'approved', $property->id, $data['role'], $data['reason']);
        });

        return redirect()->route('admin.users.index')->with('status', __('Account approved.'));
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->status === 'pending', 404);

            $target->forceFill(['status' => 'rejected'])->save();
            $this->recordEvent($request, $target, 'rejected', null, null, $data['reason']);
        });

        return redirect()->route('admin.users.index')->with('status', __('Account rejected.'));
    }

    private function recordEvent(Request $request, User $target, string $event, ?int $propertyId, ?string $role, string $reason): void
    {
        DB::table('user_access_events')->insert([
            'actor_id' => $request->user()->id,
            'user_id' => $target->id,
            'property_id' => $propertyId,
            'event' => $event,
            'role' => $role,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
