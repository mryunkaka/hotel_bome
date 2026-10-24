<x-layouts::app :title="__('Work order details')">
    <div class="space-y-6">
        <header class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('my.operations.properties') }}">{{ __('Engineering properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('properties.operations.work-orders.index', $property) }}">{{ __('Work orders') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Details') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ $order->title }}</flux:heading>
            <flux:text>{{ $property->name }} · {{ __(ucfirst(str_replace('_', ' ', $order->status))) }}</flux:text>
        </header>

        <dl class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-2">
            <div><dt class="text-sm text-zinc-500">{{ __('Room') }}</dt><dd>{{ $room ?? __('Property-wide') }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Asset') }}</dt><dd>{{ $asset ? $asset->code.' — '.$asset->name : __('No asset') }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Assignee') }}</dt><dd>{{ $assignee ?? __('Unassigned') }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Created by') }}</dt><dd>{{ $creator }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-zinc-500">{{ __('Description') }}</dt><dd class="whitespace-pre-wrap break-words">{{ $order->description ?: __('No description.') }}</dd></div>
        </dl>

        <section class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" aria-labelledby="work-order-history-heading">
            <h2 id="work-order-history-heading" class="font-semibold">{{ __('History') }}</h2>
            @if ($events->isEmpty())
                <p class="text-sm text-zinc-500">{{ __('No history available.') }}</p>
            @else
                <ol class="space-y-3">
                    @foreach ($events as $event)
                        <li class="border-s-2 border-zinc-300 ps-3 dark:border-zinc-600">
                            <p class="font-medium">{{ __(ucfirst(str_replace('_', ' ', $event->event_type))) }}</p>
                            <p class="text-sm text-zinc-500">{{ $event->actor_name }} · {{ $event->created_at }}</p>
                            @if ($event->from_status || $event->to_status)
                                <p class="text-sm">{{ $event->from_status ? __(ucfirst(str_replace('_', ' ', $event->from_status))): __('New') }} → {{ $event->to_status ? __(ucfirst(str_replace('_', ' ', $event->to_status))): __('Unassigned') }}</p>
                            @endif
                            @if ($event->reason)<p class="break-words text-sm">{{ $event->reason }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-layouts::app>
