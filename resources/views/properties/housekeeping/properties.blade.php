<x-layouts::app :title="__('My properties')">
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">{{ __('My properties') }}</flux:heading>
            <flux:text>{{ __('Assigned housekeeping workspaces.') }}</flux:text>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($properties as $property)
                <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="min-w-0">
                        <h2 class="break-words font-medium">{{ $property->name }}</h2>
                        <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $property->code }}</p>
                    </div>
                    <a class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-900 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:hover:bg-amber-900" href="{{ route('properties.housekeeping.board', $property->id) }}">{{ __('Open board') }}</a>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No housekeeping properties are assigned.') }}</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ __('Assigned housekeeping properties') }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Property') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Code') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($properties as $property)
                        <tr>
                            <td class="px-4 py-3">{{ $property->name }}</td>
                            <td class="px-4 py-3">{{ $property->code }}</td>
                            <td class="px-4 py-3 text-end">
                                <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.housekeeping.board', $property->id) }}">{{ __('Open board') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-500">{{ __('No housekeeping properties are assigned.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
