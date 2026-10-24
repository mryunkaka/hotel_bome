<x-layouts::app :title="__('Engineering properties')">
    <div class="space-y-6">
        <div><flux:heading size="xl">{{ __('Engineering properties') }}</flux:heading><flux:text>{{ __('Assigned operations workspaces.') }}</flux:text></div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($properties as $property)
                <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div><h2 class="font-medium">{{ $property->name }}</h2><p class="font-mono text-sm text-zinc-500">{{ $property->code }}</p></div>
                    <div class="flex flex-wrap gap-2"><a class="inline-flex min-h-11 items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm underline dark:border-zinc-600" href="{{ route('properties.operations.work-orders.index', $property->id) }}">{{ __('Open work orders') }}</a><a class="inline-flex min-h-11 items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm underline dark:border-zinc-600" href="{{ route('properties.operations.assets.index', $property->id) }}">{{ __('Manage assets') }}</a></div>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 p-8 text-zinc-500 dark:border-zinc-700">{{ __('No operations properties are assigned.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
