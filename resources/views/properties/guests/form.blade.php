<x-layouts::app :title="$guest->exists ? __('Edit guest') : __('Add guest')">
    <div class="mx-auto max-w-2xl space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('properties.index')">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('properties.guests.index', $property)">{{ $property->name }} — {{ __('Guests') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $guest->exists ? __('Edit') : __('New guest') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ $guest->exists ? __('Edit guest') : __('Add guest') }}</flux:heading>
            <flux:text>{{ __('Operational guest profile — display name and internal notes only.') }}</flux:text>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $guest->exists ? route('properties.guests.update', [$property, $guest]) : route('properties.guests.store', $property) }}" class="space-y-6">
            @csrf
            @if ($guest->exists)
                @method('PUT')
            @endif

            <flux:input name="display_name" :label="__('Display name')" :value="old('display_name', $guest->display_name)" maxlength="200" required autofocus />
            <flux:textarea name="notes" :label="__('Notes')" maxlength="500" rows="3">{{ old('notes', $guest->notes) }}</flux:textarea>

            @if (! $guest->is_active && $guest->exists)
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('This guest profile is inactive.') }}" />
            @endif

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary">{{ __('Save guest') }}</flux:button>
                <flux:button :href="route('properties.guests.index', $property)" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
