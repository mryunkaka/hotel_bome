<x-layouts::app :title="__('Guest properties')">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:breadcrumbs><flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Overview') }}</flux:breadcrumbs.item><flux:breadcrumbs.item>{{ __('Guest properties') }}</flux:breadcrumbs.item></flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Guest properties') }}</flux:heading>
            <flux:text>{{ __('Choose an active property to manage its guest profiles.') }}</flux:text>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($properties as $property)
                <a href="{{ route('properties.guests.index', $property) }}" class="rounded-xl border border-zinc-200 bg-white p-5 hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="sm">{{ $property->name }}</flux:heading>
                    <flux:text class="mt-1">{{ $property->code }}</flux:text>
                    <span class="mt-4 inline-flex min-h-11 items-center text-sm font-medium text-emerald-800 underline dark:text-emerald-300">{{ __('Open guests') }}</span>
                </a>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white p-6 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No active properties.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
