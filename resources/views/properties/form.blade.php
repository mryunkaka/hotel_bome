<x-layouts::app :title="$property->exists ? __('Edit property') : __('Add property')">
    <div class="mx-auto max-w-2xl space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('properties.index') }}">{{ __('Properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $property->exists ? __('Edit') : __('New property') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ $property->exists ? __('Edit property') : __('Add property') }}</flux:heading>
            <flux:text>{{ __('Property codes must be unique and use letters, numbers, and hyphens.') }}</flux:text>
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

        <form method="POST" action="{{ $property->exists ? route('properties.update', $property) : route('properties.store') }}" class="space-y-6">
            @csrf
            @if ($property->exists)
                @method('PUT')
            @endif

            <flux:input name="name" :label="__('Property name')" :value="old('name', $property->name)" maxlength="160" required autofocus />
            <flux:input name="code" :label="__('Property code')" :value="old('code', $property->code)" maxlength="20" required />

            @if (! $property->is_active && $property->exists)
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('This property is inactive and cannot be reactivated here.') }}" />
            @endif

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary">{{ __('Save property') }}</flux:button>
                <flux:button :href="route('properties.index')" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
