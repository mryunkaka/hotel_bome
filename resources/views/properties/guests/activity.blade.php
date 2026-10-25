<x-layouts::app :title="__('Guest activity — :property', ['property' => $property->name])">
    <div class="space-y-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('properties.guests.index', $property)">{{ __('Guests') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Guest activity') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl">{{ __('Guest activity') }}</flux:heading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-start text-sm">
                <thead><tr class="border-b"><th class="p-3">{{ __('Guest') }}</th><th class="p-3">{{ __('Activity') }}</th><th class="p-3">{{ __('Reason') }}</th><th class="p-3">{{ __('User') }}</th><th class="p-3">{{ __('When') }}</th></tr></thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800"><td class="p-3">{{ $event->display_name }}</td><td class="p-3">{{ __($event->event_type) }}</td><td class="p-3">{{ $event->reason }}</td><td class="p-3">{{ $event->actor_name }}</td><td class="p-3">{{ $event->created_at }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="5">{{ __('No guest activity yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    </div>
</x-layouts::app>
