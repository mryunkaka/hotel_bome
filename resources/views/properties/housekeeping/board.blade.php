<x-layouts::app :title="__('Housekeeping board')">
    <div class="space-y-6">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('my.properties.index') }}">{{ __('My properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $property->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <flux:heading size="xl">{{ __('Housekeeping board') }}</flux:heading>
                    <flux:text>{{ $property->name }}</flux:text>
                </div>
                <a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600" href="{{ route('properties.housekeeping.tasks.index', $property) }}">{{ __('Task queue') }}</a>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-3 md:hidden">
            @forelse ($rooms as $room)
                <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-words font-mono font-semibold">{{ $room->code }}</h2>
                            <p class="mt-1 break-words text-sm text-zinc-600 dark:text-zinc-300">{{ $room->room_type_name }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-zinc-800">{{ ucfirst($room->housekeeping_status ?? 'unknown') }}</span>
                    </div>
                    <form method="POST" action="{{ route('properties.housekeeping.status', [$property, $room->id]) }}" class="grid gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        @csrf
                        @method('PATCH')
                        <label class="text-sm font-medium" for="mobile_status_{{ $room->id }}">{{ __('New status for room :room', ['room' => $room->code]) }}</label>
                        <select id="mobile_status_{{ $room->id }}" name="status" required class="min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                            @foreach (match ($room->housekeeping_status) {
                                'dirty' => ['clean'],
                                'clean' => ['dirty', 'inspected'],
                                'inspected' => ['dirty'],
                                default => [],
                            } as $nextStatus)
                                <option value="{{ $nextStatus }}">{{ ucfirst($nextStatus) }}</option>
                            @endforeach
                        </select>
                        <flux:input name="reason" :label="__('Reason (optional)')" maxlength="500" />
                        <flux:button class="w-full" type="submit" variant="primary">{{ __('Save') }}</flux:button>
                    </form>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No active rooms.') }}</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ __('Housekeeping status board') }} — {{ $property->name }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Room') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Room type') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Housekeeping status') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Update status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($rooms as $room)
                        <tr>
                            <td class="px-4 py-3">{{ $room->code }}</td>
                            <td class="px-4 py-3">{{ $room->room_type_name }}</td>
                            <td class="px-4 py-3">{{ ucfirst($room->housekeeping_status ?? 'unknown') }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('properties.housekeeping.status', [$property, $room->id]) }}" class="flex flex-wrap items-end justify-end gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="sr-only" for="status_{{ $room->id }}">{{ __('New status for room :room', ['room' => $room->code]) }}</label>
                                        <select id="status_{{ $room->id }}" name="status" required class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                                            @foreach (match ($room->housekeeping_status) {
                                                'dirty' => ['clean'],
                                                'clean' => ['dirty', 'inspected'],
                                                'inspected' => ['dirty'],
                                                default => [],
                                            } as $nextStatus)
                                                <option value="{{ $nextStatus }}">{{ ucfirst($nextStatus) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <flux:input name="reason" :label="__('Reason (optional)')" maxlength="500" />
                                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">{{ __('No active rooms.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rooms->links() }}
    </div>
</x-layouts::app>
