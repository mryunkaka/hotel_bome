<x-layouts::app :title="match ($kind) { 'outlet' => __('Outlets'), 'room_type' => __('Room types'), default => __('Rooms') }">
    @php
        $title = match ($kind) { 'outlet' => __('Outlets'), 'room_type' => __('Room types'), default => __('Rooms') };
        $plural = match ($kind) { 'outlet' => __('outlets'), 'room_type' => __('room types'), default => __('rooms') };
        $createLabel = match ($kind) { 'outlet' => __('Add outlet'), 'room_type' => __('Add room type'), default => __('Add room') };
        $storeRoute = match ($kind) { 'outlet' => 'properties.outlets.store', 'room_type' => 'properties.room-types.store', default => 'properties.rooms.store' };
        $editRoute = match ($kind) { 'outlet' => 'properties.outlets.edit', 'room_type' => 'properties.room-types.edit', default => 'properties.rooms.edit' };
        $deactivateRoute = match ($kind) { 'outlet' => 'properties.outlets.deactivate', 'room_type' => 'properties.room-types.deactivate', default => 'properties.rooms.deactivate' };
    @endphp

    <div class="space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('properties.index') }}">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $property->name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <flux:heading size="xl">{{ $title }}</flux:heading>
                    <flux:text>{{ __('Manage :items for :property.', ['items' => $plural, 'property' => $property->name]) }}</flux:text>
                </div>
                <nav aria-label="{{ __('Property inventory') }}" class="flex flex-wrap gap-4 text-sm">
                    <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.outlets.index', $property) }}">{{ __('Outlets') }}</a>
                    <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.room-types.index', $property) }}">{{ __('Room types') }}</a>
                    <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.rooms.index', $property) }}">{{ __('Rooms') }}</a>
                    @if ($kind === 'room')
                        <a class="text-amber-800 underline dark:text-amber-300" href="{{ route('properties.rooms.history', $property) }}">{{ __('History') }}</a>
                    @endif
                </nav>
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

        @if ($kind === 'room')
            <form method="GET" action="{{ route('properties.rooms.index', $property) }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="min-w-40 flex-1">
                    <flux:input name="q" :label="__('Room code')" :value="$search" maxlength="40" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="housekeeping_status_filter">{{ __('Housekeeping status') }}</label>
                    <select id="housekeeping_status_filter" name="housekeeping_status" class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                        <option value="all" @selected($statusFilter === 'all')>{{ __('All statuses') }}</option>
                        @foreach (['dirty', 'clean', 'inspected'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($statusFilter === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="active_filter">{{ __('Lifecycle') }}</label>
                    <select id="active_filter" name="active" class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                        @foreach (['all' => __('All rooms'), 'active' => __('Active'), 'inactive' => __('Inactive')] as $filterValue => $filterLabel)
                            <option value="{{ $filterValue }}" @selected($activeFilter === $filterValue)>{{ $filterLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <flux:button type="submit">{{ __('Filter') }}</flux:button>
            </form>

            @if (! $property->is_active)
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('This property is inactive. Existing inventory remains visible; new inventory is disabled.') }}" />
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    <form method="POST" action="{{ route($storeRoute, $property) }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        @csrf
                        <div class="min-w-48 flex-1">
                            <label class="mb-2 block text-sm font-medium" for="room_type_id">{{ __('Room type') }}</label>
                            <select id="room_type_id" name="room_type_id" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                                <option value="">{{ __('Select room type') }}</option>
                                @foreach ($roomTypes as $roomType)
                                    <option value="{{ $roomType->id }}" @selected((string) old('room_type_id') === (string) $roomType->id)>{{ $roomType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-40 flex-1">
                            <flux:input name="code" :label="__('Code')" :value="old('code')" maxlength="20" required />
                        </div>
                        <flux:button type="submit" variant="primary">{{ $createLabel }}</flux:button>
                    </form>
                    <form method="POST" action="{{ route('properties.room-blocks.store', $property) }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    @csrf
                    <div class="min-w-48 flex-1">
                        <label class="mb-2 block text-sm font-medium" for="block_room_id">{{ __('Room') }}</label>
                        <select id="block_room_id" name="room_id" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                            <option value="">{{ __('Select room') }}</option>
                            @foreach ($activeRooms as $activeRoom)
                                <option value="{{ $activeRoom->id }}" @selected((string) old('room_id') === (string) $activeRoom->id)>{{ $activeRoom->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium" for="block_start_date">{{ __('From date (inclusive)') }}</label>
                        <input id="block_start_date" type="date" name="start_date" value="{{ old('start_date') }}" required class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium" for="block_end_date">{{ __('Through date (inclusive)') }}</label>
                        <input id="block_end_date" type="date" name="end_date" value="{{ old('end_date') }}" required class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                    </div>
                    <div class="min-w-48 flex-1">
                        <flux:input name="reason" :label="__('Reason')" :value="old('reason')" maxlength="500" required />
                    </div>
                    <flux:button type="submit" variant="primary">{{ __('Add room block') }}</flux:button>
                    </form>
                </div>
            @endif

            <div class="space-y-3 md:hidden">
                @forelse ($items as $item)
                    <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-mono font-semibold">{{ $item->code }}</h2>
                                <p class="mt-1 break-words text-sm text-zinc-600 dark:text-zinc-300">{{ $item->room_type_name }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-zinc-800">{{ $item->is_active ? __('Active') : __('Inactive') }}</span>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                            <div><dt class="text-zinc-500">{{ __('Housekeeping status') }}</dt><dd class="mt-1 font-medium">{{ ucfirst((string) $item->housekeeping_status) ?: __('Not set') }}</dd></div>
                            <div><dt class="text-zinc-500">{{ __('Room blocks') }}</dt><dd class="mt-1 font-medium">{{ $blocksByRoom->get($item->id, collect())->count() }}</dd></div>
                        </dl>
                        @if ($blocksByRoom->get($item->id, collect())->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($blocksByRoom->get($item->id, collect()) as $block)
                                    <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                        <p>{{ $block->start_date }} – {{ $block->end_date }}</p>
                                        <p class="mt-1 break-words text-zinc-600 dark:text-zinc-300">{{ $block->reason }}</p>
                                        @if ($property->is_active && $item->is_active)
                                            <form method="POST" action="{{ route('properties.room-blocks.cancel', [$property, $item->id, $block->id]) }}" class="mt-3 grid gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="text-sm font-medium" for="mobile_cancel_reason_{{ $block->id }}">{{ __('Cancellation reason') }}</label>
                                                <input id="mobile_cancel_reason_{{ $block->id }}" name="reason" required maxlength="500" class="min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                                <button class="min-h-11 rounded-lg px-3 py-2 text-start text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Cancel block') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($property->is_active && $item->is_active)
                            @php
                                $mobileNextStatuses = match ($item->housekeeping_status) {
                                    'dirty' => ['clean'],
                                    'clean' => ['dirty', 'inspected'],
                                    'inspected' => ['dirty'],
                                    default => [],
                                };
                            @endphp
                            <div class="grid gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                @if ($mobileNextStatuses !== [])
                                    <form method="POST" action="{{ route('properties.rooms.status.update', [$property, $item->id]) }}" class="grid gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-sm font-medium" for="mobile_next_status_{{ $item->id }}">{{ __('Next housekeeping status') }}</label>
                                        <select id="mobile_next_status_{{ $item->id }}" name="status" required class="min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                            @foreach ($mobileNextStatuses as $nextStatus)
                                                <option value="{{ $nextStatus }}">{{ ucfirst($nextStatus) }}</option>
                                            @endforeach
                                        </select>
                                        <label class="text-sm font-medium" for="mobile_status_reason_{{ $item->id }}">{{ __('Reason (optional)') }}</label>
                                        <input id="mobile_status_reason_{{ $item->id }}" name="reason" maxlength="500" class="min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                        <button class="min-h-11 rounded-lg px-3 py-2 text-start text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" type="submit">{{ __('Update status') }}</button>
                                    </form>
                                @endif
                                <a class="min-h-11 rounded-lg px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route($editRoute, [$property, $item->id]) }}">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route($deactivateRoute, [$property, $item->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="min-h-11 w-full rounded-lg px-3 py-2 text-start text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Deactivate') }}</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No :items yet.', ['items' => $plural]) }}</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <table class="w-full text-start text-sm">
                    <caption class="sr-only">{{ __('Room status board') }} — {{ $property->name }}</caption>
                    <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Room') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Room type') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Housekeeping status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Lifecycle') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Uncancelled room blocks (past blocks remain listed)') }}</th>
                            <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $item->code }}</td>
                                <td class="px-4 py-3">{{ $item->room_type_name }}</td>
                                <td class="px-4 py-3">{{ ucfirst((string) $item->housekeeping_status) }}</td>
                                <td class="px-4 py-3">{{ $item->is_active ? __('Active') : __('Inactive') }}</td>
                                <td class="px-4 py-3">
                                    @forelse ($blocksByRoom->get($item->id, collect()) as $block)
                                        <div class="mb-2 rounded border border-zinc-200 p-2 dark:border-zinc-700">
                                            <p>{{ $block->start_date }} – {{ $block->end_date }}: {{ $block->reason }}</p>
                                            @if ($property->is_active && $item->is_active)
                                                <form method="POST" action="{{ route('properties.room-blocks.cancel', [$property, $item->id, $block->id]) }}" class="mt-2 flex flex-wrap items-end gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="sr-only" for="cancel_reason_{{ $block->id }}">{{ __('Cancellation reason') }}</label>
                                                    <input id="cancel_reason_{{ $block->id }}" name="reason" required maxlength="500" placeholder="{{ __('Cancellation reason') }}" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-600">
                                                    <button class="text-red-700 underline dark:text-red-300" type="submit">{{ __('Cancel block') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-zinc-500">{{ __('None') }}</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @if ($property->is_active && $item->is_active)
                                        @php
                                            $nextStatuses = match ($item->housekeeping_status) {
                                                'dirty' => ['clean'],
                                                'clean' => ['dirty', 'inspected'],
                                                'inspected' => ['dirty'],
                                                default => [],
                                            };
                                        @endphp
                                        @if ($nextStatuses !== [])
                                            <form method="POST" action="{{ route('properties.rooms.status.update', [$property, $item->id]) }}" class="mb-2 flex flex-col items-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="sr-only" for="next_status_{{ $item->id }}">{{ __('Next housekeeping status') }}</label>
                                                <select id="next_status_{{ $item->id }}" name="status" required class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-600">
                                                    @foreach ($nextStatuses as $nextStatus)
                                                        <option value="{{ $nextStatus }}">{{ ucfirst($nextStatus) }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="sr-only" for="status_reason_{{ $item->id }}">{{ __('Reason (optional)') }}</label>
                                                <input id="status_reason_{{ $item->id }}" name="reason" maxlength="500" placeholder="{{ __('Reason (optional)') }}" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-600">
                                                <button class="text-amber-800 underline dark:text-amber-300" type="submit">{{ __('Update status') }}</button>
                                            </form>
                                        @endif
                                        <a class="me-3 text-amber-800 underline dark:text-amber-300" href="{{ route($editRoute, [$property, $item->id]) }}">{{ __('Edit') }} <span class="sr-only">{{ $item->code }}</span></a>
                                        <form method="POST" action="{{ route($deactivateRoute, [$property, $item->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-red-700 underline dark:text-red-300" type="submit">{{ __('Deactivate') }} <span class="sr-only">{{ $item->code }}</span></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-zinc-500" colspan="6">{{ __('No :items yet.', ['items' => $plural]) }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        @else
            @if (! $property->is_active)
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('This property is inactive. Existing inventory remains visible; new inventory is disabled.') }}" />
            @else
                <form method="POST" action="{{ route($storeRoute, $property) }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    @csrf
                    <div class="min-w-56 flex-1">
                        <flux:input name="name" :label="$kind === 'outlet' ? __('Outlet name') : __('Room type name')" :value="old('name')" maxlength="120" required />
                    </div>
                    <div class="min-w-40 flex-1">
                        <flux:input name="code" :label="__('Code')" :value="old('code')" maxlength="20" required />
                    </div>
                    <flux:button type="submit" variant="primary">{{ $createLabel }}</flux:button>
                </form>
            @endif

            <div class="space-y-3 md:hidden">
                @forelse ($items as $item)
                    <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words font-medium">{{ $item->name }}</h2>
                                <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $item->code }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-zinc-800">{{ $item->is_active ? __('Active') : __('Inactive') }}</span>
                        </div>
                        @if ($property->is_active && $item->is_active)
                            <nav aria-label="{{ __('Actions for :item', ['item' => $item->name]) }}" class="grid grid-cols-2 gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <a class="min-h-11 rounded-lg px-3 py-2 text-center text-sm font-medium text-amber-800 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-zinc-800" href="{{ route($editRoute, [$property, $item->id]) }}">{{ __('Edit') }}</a>
                                @if ($kind === 'room_type' && $roomTypesWithActiveRooms->contains($item->id))
                                    <button class="min-h-11 rounded-lg px-3 py-2 text-sm text-zinc-500" type="button" disabled title="{{ __('Deactivate active rooms first.') }}">{{ __('Deactivate') }}</button>
                                @else
                                    <form method="POST" action="{{ route($deactivateRoute, [$property, $item->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="min-h-11 w-full rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-zinc-800" type="submit">{{ __('Deactivate') }}</button>
                                    </form>
                                @endif
                            </nav>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border border-zinc-200 bg-white px-4 py-8 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No :items yet.', ['items' => $plural]) }}</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <table class="w-full text-start text-sm">
                    <caption class="sr-only">{{ $title }} — {{ $property->name }}</caption>
                    <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Code') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-end font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $item->code }}</td>
                                <td class="px-4 py-3">{{ $item->name }}</td>
                                <td class="px-4 py-3">{{ $item->is_active ? __('Active') : __('Inactive') }}</td>
                                <td class="px-4 py-3 text-end">
                                    @if ($property->is_active && $item->is_active)
                                        <a class="me-3 text-amber-800 underline dark:text-amber-300" href="{{ route($editRoute, [$property, $item->id]) }}">{{ __('Edit') }} <span class="sr-only">{{ $item->code }}</span></a>
                                        @if ($kind === 'room_type' && $roomTypesWithActiveRooms->contains($item->id))
                                            <button class="text-zinc-500 underline" type="button" disabled title="{{ __('Deactivate active rooms first.') }}">{{ __('Deactivate') }}</button>
                                        @else
                                            <form method="POST" action="{{ route($deactivateRoute, [$property, $item->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-red-700 underline dark:text-red-300" type="submit">{{ __('Deactivate') }} <span class="sr-only">{{ $item->code }}</span></button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-zinc-500" colspan="4">{{ __('No :items yet.', ['items' => $plural]) }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        @endif
    </div>
</x-layouts::app>
