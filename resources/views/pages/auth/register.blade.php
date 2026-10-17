<x-layouts::auth :title="__('Create account')">
    <div class="space-y-8">
        <div class="space-y-2">
            <p class="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-400">{{ __('Get started') }}</p>
            <flux:heading size="xl">{{ __('Create your account') }}</flux:heading>
            <flux:text>{{ __('Set up your access to the hotel operations workspace.') }}</flux:text>
        </div>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-circle" heading="{{ $errors->first() }}" />
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
            @csrf
            <flux:input name="name" :label="__('Full name')" :value="old('name')" type="text" required autofocus autocomplete="name" placeholder="{{ __('Your name') }}" />
            <flux:input name="email" :label="__('Work email')" :value="old('email')" type="email" required autocomplete="email" placeholder="you@hotel.com" />
            <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />
            <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />
            <flux:button type="submit" variant="primary" class="w-full" icon:trailing="arrow-right">{{ __('Create account') }}</flux:button>
        </form>

        <div class="text-center"><flux:text size="sm">{{ __('Already have an account?') }} <flux:link :href="route('login')" wire:navigate>{{ __('Sign in') }}</flux:link></flux:text></div>
    </div>
</x-layouts::auth>
