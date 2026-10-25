<x-layouts::app :title="__('Guest report — :property', ['property' => $property->name])">
    <div class="space-y-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('properties.guests.index', $property)">{{ __('Guests') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Guest report') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl">{{ __('Guest report') }}</flux:heading>
        <flux:text>{{ __('Aggregate counts for :property. This report does not export guest profiles.', ['property' => $property->name]) }}</flux:text>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['active' => __('Active profiles'), 'inactive' => __('Inactive profiles'), 'validated' => __('Validated profiles'), 'pending_deactivation' => __('Pending deactivation requests')] as $key => $label)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"><dt class="text-sm text-zinc-500">{{ $label }}</dt><dd class="mt-2 text-2xl font-semibold">{{ $report[$key] }}</dd></div>
            @endforeach
        </dl>
    </div>
</x-layouts::app>
