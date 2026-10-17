<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <flux:breadcrumbs><flux:breadcrumbs.item>{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item><flux:breadcrumbs.item>{{ __('Overview') }}</flux:breadcrumbs.item></flux:breadcrumbs>
                <flux:heading size="xl">{{ __('Good day, :name', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text>{{ __('Here is your hotel operations overview.') }}</flux:text>
            </div>
            <flux:badge icon="calendar-days" color="zinc">{{ now()->format('l, d F Y') }}</flux:badge>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => __('Occupancy'), 'icon' => 'building-office-2', 'note' => __('Room inventory not connected')],
                ['label' => __('Arrivals today'), 'icon' => 'arrow-down-on-square', 'note' => __('Reservation data will appear here')],
                ['label' => __('Departures today'), 'icon' => 'arrow-up-on-square', 'note' => __('Reservation data will appear here')],
                ['label' => __('Revenue this month'), 'icon' => 'banknotes', 'note' => __('Financial data will appear here')],
            ] as $metric)
                <flux:card class="space-y-5">
                    <div class="flex items-center justify-between gap-3">
                        <flux:text>{{ $metric['label'] }}</flux:text>
                        <span class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-800 dark:bg-amber-400/10 dark:text-amber-300"><flux:icon :name="$metric['icon']" class="size-5" /></span>
                    </div>
                    <div class="space-y-1"><flux:heading size="xl">—</flux:heading><flux:text size="sm">{{ $metric['note'] }}</flux:text></div>
                </flux:card>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)]">
            <flux:card class="min-h-72 space-y-6">
                <div class="flex items-start justify-between gap-4"><div class="space-y-1"><flux:heading>{{ __('Today at a glance') }}</flux:heading><flux:text>{{ __('Your daily front desk and housekeeping snapshot.') }}</flux:text></div><flux:badge color="amber" icon="sparkles">{{ __('Getting started') }}</flux:badge></div>
                <div class="flex min-h-40 flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 px-6 text-center dark:border-zinc-700">
                    <span class="mb-3 flex size-11 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon name="calendar-days" class="size-5" /></span>
                    <flux:heading size="sm">{{ __('Your operations will show up here') }}</flux:heading>
                    <flux:text class="mt-1 max-w-md">{{ __('Connect rooms and reservations to see arrivals, departures, and occupancy in one place.') }}</flux:text>
                </div>
            </flux:card>

            <flux:card class="space-y-5">
                <div class="space-y-1"><flux:heading>{{ __('Workspace setup') }}</flux:heading><flux:text>{{ __('The foundation is ready for your hotel data.') }}</flux:text></div>
                <flux:separator />
                <div class="space-y-4">
                    <div class="flex gap-3"><flux:badge color="green" icon="check" size="sm" /><div><flux:heading size="sm">{{ __('Livewire starter kit') }}</flux:heading><flux:text size="sm">{{ __('Authentication and account access are ready.') }}</flux:text></div></div>
                    <div class="flex gap-3"><flux:badge color="amber" icon="minus" size="sm" /><div><flux:heading size="sm">{{ __('Hotel operations') }}</flux:heading><flux:text size="sm">{{ __('Rooms, reservations, and finance are ready to be added.') }}</flux:text></div></div>
                </div>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
