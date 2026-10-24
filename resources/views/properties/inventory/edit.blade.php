<x-layouts::app :title="__('Edit :item', ['item' => $kind === 'outlet' ? __('outlet') : ($kind === 'room_type' ? __('room type') : __('room'))])">
    @php
        $title = $kind === 'outlet' ? __('Edit outlet') : ($kind === 'room_type' ? __('Edit room type') : __('Edit room'));
        $indexRoute = match ($kind) { 'outlet' => 'properties.outlets.index', 'room_type' => 'properties.room-types.index', default => 'properties.rooms.index' };
        $updateRoute = match ($kind) { 'outlet' => 'properties.outlets.update', 'room_type' => 'properties.room-types.update', default => 'properties.rooms.update' };
    @endphp

    <div class="mx-auto max-w-2xl space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('properties.index') }}">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $property->name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route($indexRoute, $property) }}">{{ $kind === 'outlet' ? __('Outlets') : ($kind === 'room_type' ? __('Room types') : __('Rooms')) }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ $title }}</flux:heading>
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

        <form method="POST" action="{{ route($updateRoute, [$property, $item->id]) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @if ($kind !== 'room')
                <flux:input name="name" :label="$kind === 'outlet' ? __('Outlet name') : __('Room type name')" :value="old('name', $item->name)" maxlength="120" required />
            @else
                <flux:input :label="__('Room type')" :value="$item->room_type_name" readonly />
            @endif

            <flux:input name="code" :label="__('Code')" :value="old('code', $item->code)" maxlength="20" required />

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
                <flux:button :href="route($indexRoute, $property)" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
