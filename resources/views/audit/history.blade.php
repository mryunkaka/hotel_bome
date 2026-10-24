<x-layouts::app :title="__('Audit history')">
    <div class="space-y-6">
        <header class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Audit history') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Audit history') }}</flux:heading>
            <flux:text>{{ __('Read-only history of recorded access and room-operation events. No retention or deletion policy is configured here.') }}</flux:text>
        </header>

        <form method="GET" action="{{ route('admin.audit.index') }}" class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="source" class="mb-1 block text-sm font-medium">{{ __('Source') }}</label>
                <select id="source" name="source" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                    <option value="">{{ __('All sources') }}</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ __(str($source)->replace('_', ' ')->title()->toString()) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="property_id" class="mb-1 block text-sm font-medium">{{ __('Property') }}</label>
                <select id="property_id" name="property_id" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                    <option value="">{{ __('All properties') }}</option>
                    @foreach ($properties as $property)
                        <option value="{{ $property->id }}" @selected((string) ($filters['property_id'] ?? '') === (string) $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="mb-1 block text-sm font-medium">{{ __('From') }}</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
            </div>
            <div>
                <label for="to" class="mb-1 block text-sm font-medium">{{ __('To') }}</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
            </div>
            <div class="flex items-end gap-2">
                <flux:button type="submit" variant="primary">{{ __('Filter') }}</flux:button>
                <flux:button href="{{ route('admin.audit.index') }}" variant="ghost">{{ __('Clear') }}</flux:button>
            </div>
            @foreach (['source', 'property_id', 'from', 'to'] as $field)
                @error($field)<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            @endforeach
        </form>

        <div class="space-y-3 md:hidden">
            @forelse ($events as $event)
                <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="font-medium">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}</h2><p class="mt-1 break-all text-xs text-zinc-500">{{ $event->source }}:{{ $event->source_id }}</p></div>
                        <time class="shrink-0 text-xs text-zinc-500">{{ $event->occurred_at }}</time>
                    </div>
                    <dl class="grid grid-cols-2 gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <div><dt class="text-zinc-500">{{ __('Source') }}</dt><dd class="mt-1">{{ __(str($event->source)->replace('_', ' ')->title()->toString()) }}</dd></div>
                        <div><dt class="text-zinc-500">{{ __('Property') }}</dt><dd class="mt-1 break-words">{{ $event->property_name ?? __('Not applicable') }}</dd></div>
                        <div><dt class="text-zinc-500">{{ __('Actor') }}</dt><dd class="mt-1 break-words">{{ $event->actor_name ?? __('Unavailable') }}</dd></div>
                        <div><dt class="text-zinc-500">{{ __('Subject') }}</dt><dd class="mt-1 break-words">{{ $event->subject_name ?? __('Unavailable') }}</dd></div>
                        <div class="col-span-2"><dt class="text-zinc-500">{{ __('Change') }}</dt><dd class="mt-1 break-words">{{ $event->previous_value }}@if ($event->previous_value !== null || $event->new_value !== null) → @endif{{ $event->new_value }}</dd></div>
                        @if ($event->reason !== null)
                            <div class="col-span-2"><dt class="text-zinc-500">{{ __('Reason') }}</dt><dd class="mt-1 break-words">{{ $event->reason }}</dd></div>
                        @endif
                    </dl>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ __('No audit events match these filters.') }}</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-zinc-200 dark:border-zinc-700"><th scope="col" class="p-3">{{ __('Time') }}</th><th scope="col" class="p-3">{{ __('Source') }}</th><th scope="col" class="p-3">{{ __('Reference') }}</th><th scope="col" class="p-3">{{ __('Event') }}</th><th scope="col" class="p-3">{{ __('Property') }}</th><th scope="col" class="p-3">{{ __('Actor') }}</th><th scope="col" class="p-3">{{ __('Subject') }}</th><th scope="col" class="p-3">{{ __('Change') }}</th><th scope="col" class="p-3">{{ __('Reason') }}</th></tr></thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr class="border-b border-zinc-100 align-top dark:border-zinc-800">
                            <td class="whitespace-nowrap p-3">{{ $event->occurred_at }}</td>
                            <td class="p-3">{{ __(str($event->source)->replace('_', ' ')->title()->toString()) }}</td>
                            <td class="p-3">{{ $event->source }}:{{ $event->source_id }}</td>
                            <td class="p-3">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}</td>
                            <td class="p-3">{{ $event->property_name ?? __('Not applicable') }}</td>
                            <td class="p-3">{{ $event->actor_name ?? __('Unavailable') }}</td>
                            <td class="p-3">{{ $event->subject_name ?? __('Unavailable') }}</td>
                            <td class="p-3">{{ $event->previous_value }}@if ($event->previous_value !== null || $event->new_value !== null) → @endif{{ $event->new_value }}</td>
                            <td class="max-w-sm p-3">{{ $event->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="p-4 text-zinc-600 dark:text-zinc-300">{{ __('No audit events match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    </div>
</x-layouts::app>
