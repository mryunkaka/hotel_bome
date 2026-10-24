<x-layouts::app :title="__('Room history')">
    <div class="space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('properties.index') }}">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $property->name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('properties.rooms.index', $property) }}">{{ __('Rooms') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('History') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <flux:heading size="xl">{{ __('Room operational history') }}</flux:heading>
                    <flux:text>{{ __('Housekeeping status and date-block events for :property.', ['property' => $property->name]) }}</flux:text>
                </div>
                <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.rooms.index', $property) }}">{{ __('Back to room board') }}</a>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($events as $event)
                <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="font-medium">{{ match ($event->event_type) { 'housekeeping_status' => __('Housekeeping status changed'), 'room_block_added' => __('Room block added'), 'room_block_cancelled' => __('Room block cancelled'), default => __('Unknown event') } }}</h2><p class="mt-1 font-mono">{{ $event->room_code }}</p></div>
                        <time class="shrink-0 text-xs text-zinc-500">{{ $event->created_at }}</time>
                    </div>
                    <dl class="grid grid-cols-2 gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <div><dt class="text-zinc-500">{{ __('Details') }}</dt><dd class="mt-1 break-words">@if ($event->event_type === 'housekeeping_status'){{ $event->previous_status ? ucfirst($event->previous_status).' → ' : '' }}{{ ucfirst($event->status) }}@else{{ $event->start_date }} – {{ $event->end_date }}@endif</dd></div>
                        <div><dt class="text-zinc-500">{{ __('Actor ID') }}</dt><dd class="mt-1">{{ $event->actor_id ?? '—' }}</dd></div>
                        @if ($event->reason !== null)
                            <div class="col-span-2"><dt class="text-zinc-500">{{ __('Reason') }}</dt><dd class="mt-1 break-words">{{ $event->reason }}</dd></div>
                        @endif
                    </dl>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ __('No room history yet.') }}</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ __('Room operational history') }} — {{ $property->name }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Recorded at') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Room') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Event') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Details') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Reason') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Actor ID') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($events as $event)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3">{{ $event->created_at }}</td>
                            <td class="px-4 py-3 font-mono">{{ $event->room_code }}</td>
                            <td class="px-4 py-3">
                                {{ match ($event->event_type) {
                                    'housekeeping_status' => __('Housekeeping status changed'),
                                    'room_block_added' => __('Room block added'),
                                    'room_block_cancelled' => __('Room block cancelled'),
                                    default => __('Unknown event'),
                                } }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($event->event_type === 'housekeeping_status')
                                    {{ $event->previous_status ? ucfirst($event->previous_status).' → ' : '' }}{{ ucfirst($event->status) }}
                                @else
                                    {{ $event->start_date }} – {{ $event->end_date }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $event->reason }}</td>
                            <td class="px-4 py-3">{{ $event->actor_id ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-zinc-500" colspan="6">{{ __('No room history yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    </div>
</x-layouts::app>
