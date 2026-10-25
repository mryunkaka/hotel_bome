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
            <nav class="flex flex-wrap gap-2">
                <flux:button :href="route('properties.guests.activity', $property)" variant="outline">{{ __('Activity') }}</flux:button>
                <flux:button :href="route('properties.guests.report', $property)" variant="outline">{{ __('Report') }}</flux:button>
                <flux:button :href="route('properties.guests.create', $property)" variant="primary" icon="plus">{{ __('Add guest') }}</flux:button>
            </nav>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif

        @if ($pendingRequests->isNotEmpty())
            <section class="space-y-3 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950" aria-labelledby="guest-approval-heading">
                <h2 id="guest-approval-heading" class="font-semibold">{{ __('Deactivation requests') }}</h2>
                @foreach ($pendingRequests as $pendingRequest)
                    <article class="flex flex-col justify-between gap-3 border-t border-amber-200 pt-3 sm:flex-row">
                        <div><p class="font-medium">{{ $pendingRequest->display_name }}</p><p class="text-sm">{{ __('Requested by :name', ['name' => $pendingRequest->requester_name]) }} — {{ $pendingRequest->reason }}</p></div>
                        <form method="POST" action="{{ route('properties.guest-deactivation-requests.approve', [$property, $pendingRequest->id]) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <input name="reason" maxlength="500" required aria-label="{{ __('Approval reason') }}" placeholder="{{ __('Approval reason') }}" class="min-h-11 rounded-lg border px-3" />
                            <button class="min-h-11 rounded-lg bg-green-700 px-3 text-white" type="submit">{{ __('Approve') }}</button>
                        </form>
                        <form method="POST" action="{{ route('properties.guest-deactivation-requests.reject', [$property, $pendingRequest->id]) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <input name="reason" maxlength="500" required aria-label="{{ __('Rejection reason') }}" placeholder="{{ __('Rejection reason') }}" class="min-h-11 rounded-lg border px-3" />
                            <button class="min-h-11 rounded-lg border border-red-700 px-3 text-red-700" type="submit">{{ __('Reject') }}</button>
                        </form>
                    </article>
                @endforeach
            </section>
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
                        @if (in_array($role, ['supervisor', 'owner'], true) && ! $guest->is_validated)
                            <form method="POST" action="{{ route('properties.guests.validate', [$property, $guest->id]) }}" class="flex gap-2">
                                @csrf @method('PATCH')
                                <input name="reason" maxlength="500" required aria-label="{{ __('Validation reason') }}" placeholder="{{ __('Reason') }}" class="min-h-11 w-36 rounded-lg border px-2" />
                                <button class="min-h-11 py-2 text-green-700 underline dark:text-green-300" type="submit">{{ __('Validate') }}</button>
                            </form>
                        @endif
                        @if ($guest->is_active)
                            <form method="POST" action="{{ route('properties.guests.deactivate', [$property, $guest->id]) }}">
                                @csrf
                                @method('PATCH')
                                <input name="reason" maxlength="500" required aria-label="{{ __('Reason') }}" placeholder="{{ __('Reason') }}" class="min-h-11 w-full rounded-lg border px-3" />
                                <button class="min-h-11 rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Deactivate / request') }}</button>
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
                                    @if (in_array($role, ['supervisor', 'owner'], true) && ! $guest->is_validated)
                                        <form method="POST" action="{{ route('properties.guests.validate', [$property, $guest->id]) }}" class="flex gap-2">
                                            @csrf @method('PATCH')
                                            <input name="reason" maxlength="500" required aria-label="{{ __('Validation reason for :guest', ['guest' => $guest->display_name]) }}" placeholder="{{ __('Reason') }}" class="min-h-11 w-36 rounded-lg border px-2" />
                                            <button class="min-h-11 py-2 text-green-700 underline dark:text-green-300" type="submit">{{ __('Validate') }}</button>
                                        </form>
                                    @endif
                                    @if ($guest->is_active)
                                        <form method="POST" action="{{ route('properties.guests.deactivate', [$property, $guest->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input name="reason" maxlength="500" required aria-label="{{ __('Reason for :guest', ['guest' => $guest->display_name]) }}" placeholder="{{ __('Reason') }}" class="min-h-11 w-36 rounded-lg border px-2" />
                                            <button class="min-h-11 py-2 text-red-700 underline dark:text-red-300" type="submit">{{ __('Deactivate / request') }} <span class="sr-only">{{ $guest->display_name }}</span></button>
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
