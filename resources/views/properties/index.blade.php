<x-layouts::app :title="__('Properties')">
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item>{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ __('Properties') }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <flux:heading size="xl">{{ __('Properties') }}</flux:heading>
                <flux:text>{{ __('Manage hotels in this installation.') }}</flux:text>
            </div>
            <flux:button :href="route('properties.create')" variant="primary" icon="plus">{{ __('Add property') }}</flux:button>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif

        <div class="space-y-3 md:hidden">
            @forelse ($properties as $property)
                <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-words font-medium">{{ $property->name }}</h2>
                            <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $property->code }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $property->is_active ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                            {{ $property->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                    <nav aria-label="{{ __('Actions for :property', ['property' => $property->name]) }}" class="grid grid-cols-2 gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <a class="min-h-11 rounded-lg px-3 py-2 text-center text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route('properties.outlets.index', $property) }}">{{ __('Outlets') }}</a>
                        <a class="min-h-11 rounded-lg px-3 py-2 text-center text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route('properties.room-types.index', $property) }}">{{ __('Room types') }}</a>
                        <a class="min-h-11 rounded-lg px-3 py-2 text-center text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route('properties.rooms.index', $property) }}">{{ __('Rooms') }}</a>
                        <a class="min-h-11 rounded-lg px-3 py-2 text-center text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route('properties.edit', $property) }}">{{ __('Edit') }}</a>
                        @if ($property->is_active)
                            <form class="col-span-2" method="POST" action="{{ route('properties.deactivate', $property) }}">
                                @csrf
                                @method('PATCH')
                                <button class="min-h-11 w-full rounded-lg px-3 py-2 text-center text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Deactivate') }}</button>
                            </form>
                        @endif
                    </nav>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No properties yet.') }}</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ __('Hotels in this installation') }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Property') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Code') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($properties as $property)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $property->name }}</td>
                            <td class="px-4 py-3 font-mono">{{ $property->code }}</td>
                            <td class="px-4 py-3">{{ $property->is_active ? __('Active') : __('Inactive') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-3">
                                    <a class="min-h-11 py-2 text-amber-800 underline dark:text-amber-300" href="{{ route('properties.outlets.index', $property) }}">{{ __('Outlets') }} <span class="sr-only">{{ $property->name }}</span></a>
                                    <a class="min-h-11 py-2 text-amber-800 underline dark:text-amber-300" href="{{ route('properties.room-types.index', $property) }}">{{ __('Room types') }} <span class="sr-only">{{ $property->name }}</span></a>
                                    <a class="min-h-11 py-2 text-amber-800 underline dark:text-amber-300" href="{{ route('properties.rooms.index', $property) }}">{{ __('Rooms') }} <span class="sr-only">{{ $property->name }}</span></a>
                                    <a class="min-h-11 py-2 text-amber-800 underline dark:text-amber-300" href="{{ route('properties.edit', $property) }}">{{ __('Edit') }} <span class="sr-only">{{ $property->name }}</span></a>
                                    @if ($property->is_active)
                                        <form method="POST" action="{{ route('properties.deactivate', $property) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="min-h-11 py-2 text-red-700 underline dark:text-red-300" type="submit">{{ __('Deactivate') }} <span class="sr-only">{{ $property->name }}</span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-zinc-500" colspan="4">{{ __('No properties yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $properties->links() }}
    </div>
</x-layouts::app>
