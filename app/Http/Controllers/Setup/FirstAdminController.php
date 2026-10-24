<?php

namespace App\Http\Controllers\Setup;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FirstAdminController
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(): View|Response
    {
        abort_unless($this->isAvailable(), 404);

        return view('setup.first-admin');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isAvailable(), 404);

        $request->merge(['email' => strtolower($request->string('email')->toString())]);

        $data = Validator::make($request->all(), [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        DB::transaction(function () use ($data): void {
            $state = DB::table('installation_bootstrap')->where('id', 1)->lockForUpdate()->first();

            abort_if($state === null || $state->completed_at !== null || User::where('is_platform_admin', true)->exists(), 404);

            $admin = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $admin->forceFill(['status' => 'active', 'is_platform_admin' => true])->save();

            DB::table('installation_bootstrap')->where('id', 1)->update([
                'admin_user_id' => $admin->id,
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('login')->with('status', __('Initial administrator created. Sign in to continue.'));
    }

    private function isAvailable(): bool
    {
        $state = DB::table('installation_bootstrap')->where('id', 1)->first();

        return $state !== null
            && $state->completed_at === null
            && ! User::where('is_platform_admin', true)->exists();
    }
}
