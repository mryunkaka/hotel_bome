<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <flux:breadcrumbs><flux:breadcrumbs.item>{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item><flux:breadcrumbs.item>{{ __('Overview') }}</flux:breadcrumbs.item></flux:breadcrumbs>
                <flux:heading size="xl">{{ __('Good day, :name', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text>{{ __('Here is your hotel operations overview.') }}</flux:text>
            </div>
            <flux:badge icon="calendar-days" color="zinc">{{ now()->format('l, d F Y') }}</flux:badge>
        </div>

        <section aria-labelledby="workspace-links-heading" class="space-y-4">
            <div class="space-y-1">
                <flux:heading id="workspace-links-heading">{{ __('Your workspace') }}</flux:heading>
                <flux:text>{{ __('Open a section available to your account.') }}</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @if (auth()->user()->is_platform_admin)
                    <a href="{{ route('properties.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="building-office-2" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Properties and inventory') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Manage properties, rooms, room types, and outlets.') }}</flux:text>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="users" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('User approvals') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Review pending account requests.') }}</flux:text>
                    </a>
                    <a href="{{ route('admin.audit.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="clipboard-document-list" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Audit history') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Review recorded account and room events.') }}</flux:text>
                    </a>
                    <a href="{{ route('admin.users.memberships.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="key" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Property access') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Assign and review property memberships.') }}</flux:text>
                    </a>
                    <a href="{{ route('admin.users.accounts.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="user-minus" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Account lifecycle') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Review and manage active accounts.') }}</flux:text>
                    </a>
                @else
                    <a href="{{ route('my.properties.index') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="building-office-2" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('My properties') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Open property workspaces assigned to your account.') }}</flux:text>
                    </a>
                    <a href="{{ route('my.operations.properties') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="wrench-screwdriver" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Engineering work orders') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Review and update property maintenance requests.') }}</flux:text>
                    </a>
                @endif
                @if (Route::has('profile.edit'))
                    <a href="{{ route('profile.edit') }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-emerald-700 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="cog-6-tooth" class="mb-4 size-6 text-emerald-800 dark:text-emerald-300" />
                        <flux:heading size="sm">{{ __('Account settings') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Manage profile and security settings.') }}</flux:text>
                    </a>
                @endif
            </div>
        </section>

        <flux:card class="space-y-2">
            <flux:heading>{{ __('Today at a glance') }}</flux:heading>
            <flux:text>{{ __('Hotel operations are being built in verified slices.') }}</flux:text>
            <flux:text size="sm">{{ __('Reservation, occupancy, and financial summaries will appear when their workflows are implemented and verified.') }}</flux:text>
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading>{{ __('Workspace status') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                <flux:badge color="green" icon="check">{{ __('Account access') }}</flux:badge>
                @if (auth()->user()->is_platform_admin)
                    <flux:badge color="green" icon="check">{{ __('Property inventory') }}</flux:badge>
                    <flux:badge color="green" icon="check">{{ __('Account administration') }}</flux:badge>
                @else
                    <flux:badge color="green" icon="check">{{ __('Assigned property access') }}</flux:badge>
                @endif
                <flux:badge color="amber" icon="minus">{{ __('Reservations and finance not available yet') }}</flux:badge>
            </div>
        </flux:card>


    </div>
</x-layouts::app>
