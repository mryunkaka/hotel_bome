<x-layouts::auth :title="__('Initial administrator setup')">
    <div class="space-y-8">
        <div class="space-y-2">
            <p class="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-400">{{ __('Local setup') }}</p>
            <flux:heading size="xl">{{ __('Create the first administrator') }}</flux:heading>
            <flux:text>{{ __('This one-time wizard is available only on the server itself.') }}</flux:text>
        </div>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-circle" heading="{{ $errors->first() }}" />
        @endif

        <form method="POST" action="{{ route('setup.first-admin.store') }}" class="space-y-5">
            @csrf
            <flux:input name="name" :label="__('Full name')" :value="old('name')" required autofocus autocomplete="name" />
            <flux:input name="email" :label="__('Email')" :value="old('email')" type="email" required autocomplete="email" />
            <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />
            <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Create administrator') }}</flux:button>
        </form>
    </div>
</x-layouts::auth>
