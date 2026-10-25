<x-layouts::app :title="$ratePlan->exists ? __('Edit rate plan') : __('Add rate plan')">
    <div class="mx-auto max-w-2xl space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('properties.index')">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('properties.rate-plans.index', $property)">{{ $property->name }} — {{ __('Rate Plans') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $ratePlan->exists ? __('Edit') : __('New rate plan') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ $ratePlan->exists ? __('Edit rate plan') : __('Add rate plan') }}</flux:heading>
            <flux:text>{{ __('Rate codes must be unique per property. Price in IDR (whole rupiah). Tax % per D-004.') }}</flux:text>
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

        <form method="POST" action="{{ $ratePlan->exists ? route('properties.rate-plans.update', [$property, $ratePlan]) : route('properties.rate-plans.store', $property) }}" class="space-y-6">
            @csrf
            @if ($ratePlan->exists)
                @method('PUT')
            @endif

            <flux:input name="name" :label="__('Rate plan name')" :value="old('name', $ratePlan->name)" maxlength="100" required autofocus />
            <flux:input name="code" :label="__('Rate plan code')" :value="old('code', $ratePlan->code)" maxlength="20" required />
            <flux:input name="base_price_per_night" :label="__('Base price per night (IDR)')" type="number" :value="old('base_price_per_night', $ratePlan->base_price_per_night)" min="0" max="999999999" required />
            <flux:input name="tax_percentage" :label="__('Tax percentage (optional)')" type="number" step="0.01" :value="old('tax_percentage', $ratePlan->tax_percentage)" min="0" max="99.99" :placeholder="__('e.g. 10.00 for PBJT')" />
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input name="effective_from" :label="__('Effective from')" type="date" :value="old('effective_from', $ratePlan->effective_from?->format('Y-m-d'))" />
                <flux:input name="effective_until" :label="__('Effective until')" type="date" :value="old('effective_until', $ratePlan->effective_until?->format('Y-m-d'))" />
            </div>

            @if (! $ratePlan->is_active && $ratePlan->exists)
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('This rate plan is inactive.') }}" />
            @endif

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary">{{ __('Save rate plan') }}</flux:button>
                <flux:button :href="route('properties.rate-plans.index', $property)" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
