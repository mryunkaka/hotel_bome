<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Hotel workspace')" class="grid">
                <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Overview') }}</flux:sidebar.item>
                @if (auth()->user()->is_platform_admin)
                    <flux:sidebar.item icon="building-office-2" :href="route('properties.index')" :current="request()->routeIs('properties.*')" wire:navigate>{{ __('Properties') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.index', 'admin.users.approve', 'admin.users.reject')" wire:navigate>{{ __('User approvals') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="key" :href="route('admin.users.memberships.index')" :current="request()->routeIs('admin.users.memberships.*')" wire:navigate>{{ __('Property access') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="user-minus" :href="route('admin.users.accounts.index')" :current="request()->routeIs('admin.users.accounts.*', 'admin.users.suspend', 'admin.users.reactivate')" wire:navigate>{{ __('Account lifecycle') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.audit.index')" :current="request()->routeIs('admin.audit.*')" wire:navigate>{{ __('Audit history') }}</flux:sidebar.item>
                @else
                    <flux:sidebar.item icon="building-office-2" :href="route('my.properties.index')" :current="request()->routeIs('my.properties.*', 'properties.housekeeping.*')" wire:navigate>{{ __('My properties') }}</flux:sidebar.item>
                @endif
                @if (Route::has('profile.edit'))
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*')" wire:navigate>{{ __('Settings') }}</flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="book-open" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">{{ __('Livewire starter kit docs') }}</flux:sidebar.item>
        </flux:sidebar.nav>

        <x-desktop-user-menu class="hidden lg:block" />
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon:trailing="chevron-down" />
            <flux:menu>
                @if (auth()->user()->is_platform_admin)
                    <flux:menu.item :href="route('properties.index')" icon="building-office-2" wire:navigate>{{ __('Properties') }}</flux:menu.item>
                    <flux:menu.item :href="route('admin.users.index')" icon="users" wire:navigate>{{ __('User approvals') }}</flux:menu.item>
                    <flux:menu.item :href="route('admin.users.memberships.index')" icon="key" wire:navigate>{{ __('Property access') }}</flux:menu.item>
                    <flux:menu.item :href="route('admin.users.accounts.index')" icon="user-minus" wire:navigate>{{ __('Account lifecycle') }}</flux:menu.item>
                    <flux:menu.item :href="route('admin.audit.index')" icon="clipboard-document-list" wire:navigate>{{ __('Audit history') }}</flux:menu.item>
                @else
                    <flux:menu.item :href="route('my.properties.index')" icon="building-office-2" wire:navigate>{{ __('My properties') }}</flux:menu.item>
                @endif
                <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}">@csrf<flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">{{ __('Log out') }}</flux:menu.item></form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main class="mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10">
        {{ $slot }}
    </flux:main>
    @fluxScripts
</body>
</html>
