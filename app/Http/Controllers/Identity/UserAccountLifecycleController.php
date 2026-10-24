<?php

namespace App\Http\Controllers\Identity;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserAccountLifecycleController
{
    public function index(): View
    {
        return view('identity.users.accounts', [
            'users' => User::query()->where('is_platform_admin', false)->whereIn('status', ['active', 'inactive'])->orderBy('name')->paginate(25),
            'events' => DB::table('user_account_events')
                ->join('users as actors', 'actors.id', '=', 'user_account_events.actor_id')
                ->join('users as targets', 'targets.id', '=', 'user_account_events.user_id')
                ->orderByDesc('user_account_events.id')
                ->limit(100)
                ->get([
                    'user_account_events.event',
                    'user_account_events.reason',
                    'user_account_events.created_at',
                    'actors.name as actor_name',
                    'targets.name as target_name',
                ]),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->id !== $request->user()->id && ! $target->is_platform_admin, 404);
            if ($target->status !== 'active') {
                throw ValidationException::withMessages(['user' => __('Only active accounts can be suspended.')]);
            }

            $target->setRememberToken(Str::random(60));
            $target->forceFill([
                'status' => 'inactive',
                'auth_version' => ((int) $target->auth_version) + 1,
            ])->save();
            $this->recordEvent($request, $target, 'suspended', $data['reason']);
        });

        return redirect()->route('admin.users.accounts.index')->with('status', __('Account suspended.'));
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->id !== $request->user()->id && ! $target->is_platform_admin, 404);
            if ($target->status !== 'inactive') {
                throw ValidationException::withMessages(['user' => __('Only suspended accounts can be reactivated.')]);
            }

            $target->forceFill(['status' => 'active'])->save();
            $this->recordEvent($request, $target, 'reactivated', $data['reason']);
        });

        return redirect()->route('admin.users.accounts.index')->with('status', __('Account reactivated. Existing sessions remain revoked.'));
    }

    private function recordEvent(Request $request, User $target, string $event, string $reason): void
    {
        DB::table('user_account_events')->insert([
            'actor_id' => $request->user()->id,
            'user_id' => $target->id,
            'event' => $event,
            'reason' => trim($reason),
            'created_at' => now(),
        ]);
    }
}
