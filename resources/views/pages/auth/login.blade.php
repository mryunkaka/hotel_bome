<x-layouts::auth :title="__('Log in')">
    <div class="space-y-8">
        <div class="space-y-2">
            <p class="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-400">{{ __('Welcome back') }}</p>
            <flux:heading size="xl">{{ __('Sign in to your account') }}</flux:heading>
            <flux:text>{{ __('Use your work email and password to continue.') }}</flux:text>
        </div>

        <x-auth-session-status class="text-sm" :status="session('status')" />

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-circle" heading="{{ $errors->first() }}" />
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <flux:input name="email" :label="__('Work email')" :value="old('email')" type="email" required autofocus autocomplete="username" placeholder="you@hotel.com" />
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <flux:label for="password">{{ __('Password') }}</flux:label>
                    @if (Route::has('password.request'))<flux:link :href="route('password.request')" wire:navigate>{{ __('Forgot password?') }}</flux:link>@endif
                </div>
                <flux:input name="password" id="password" type="password" required autocomplete="current-password" viewable />
            </div>
            <flux:checkbox name="remember" :label="__('Keep me signed in')" :checked="old('remember')" />
            <flux:button type="submit" variant="primary" class="w-full" icon:trailing="arrow-right">{{ __('Sign in') }}</flux:button>
        </form>

        @if (Route::has('register'))
            <div class="flex items-center gap-4"><flux:separator class="flex-1" /><flux:text size="sm">{{ __('New to the team?') }}</flux:text><flux:separator class="flex-1" /></div>
            <flux:button :href="route('register')" variant="outline" class="w-full" wire:navigate>{{ __('Create an account') }}</flux:button>
        @endif
    </div>
</x-layouts::auth>
