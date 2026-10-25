<x-layouts::app :title="__('Guests — :property', ['property' => $property->name])">
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('properties.index')">{{ __('Properties') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $property->name }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ __('Guests') }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <flux:heading size="xl">{{ __('Guests') }}</flux:heading>
                <flux:text>{{ __('Guest profiles for :property. Only display name and notes — no PII.', ['property' => $property->name]) }}</flux:text>
            </div>
            <flux:button :href="route('properties.guests.create', $property)" variant="primary" icon="plus">{{ __('Add guest') }}</flux:button>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif

        <form method="GET" action="{{ route('properties.guests.index', $property) }}" class="flex flex-col gap-3 sm:flex-row">
            <flux:input name="q" :value="request('q')" :placeholder="__('Search by name…')" class="max-w-sm" />
            <flux:select name="status" class="max-w-[10rem]">
                <option value="">{{ __('All') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </flux:select>
            <flux:button type="submit" variant="outline">{{ __('Filter') }}</flux:button>
        </form>

        <div class="space-y-3 md:hidden">
            @forelse ($guests as $guest)
                <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-words font-medium">{{ $guest->display_name }}</h2>
                            @if ($guest->notes)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($guest->notes, 80) }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $guest->is_active ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                            {{ $guest->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                    <nav aria-label="{{ __('Actions for :guest', ['guest' => $guest->display_name]) }}" class="flex gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <a class="min-h-11 rounded-lg px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route('properties.guests.edit', [$property, $guest->id]) }}">{{ __('Edit') }}</a>
                        @if ($guest->is_active)
                            <form method="POST" action="{{ route('properties.guests.deactivate', [$property, $guest->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="min-h-11 rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Deactivate') }}</button>
                            </form>
                        @endif
                    </nav>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No guests yet.') }}</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ __('Guest profiles for :property', ['property' => $property->name]) }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Notes') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($guests as $guest)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $guest->display_name }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ Str::limit($guest->notes, 60) }}</td>
                            <td class="px-4 py-3">{{ $guest->is_active ? __('Active') : __('Inactive') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-3">
                                    <a class="min-h-11 py-2 text-amber-800 underline dark:text-amber-300" href="{{ route('properties.guests.edit', [$property, $guest->id]) }}">{{ __('Edit') }} <span class="sr-only">{{ $guest->display_name }}</span></a>
                                    @if ($guest->is_active)
                                        <form method="POST" action="{{ route('properties.guests.deactivate', [$property, $guest->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="min-h-11 py-2 text-red-700 underline dark:text-red-300" type="submit">{{ __('Deactivate') }} <span class="sr-only">{{ $guest->display_name }}</span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-zinc-500" colspan="4">{{ __('No guests yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $guests->links() }}
    </div>
</x-layouts::app>
