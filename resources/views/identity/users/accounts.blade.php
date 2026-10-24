<x-layouts::app :title="__('Account lifecycle')">
    <div class="space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('admin.users.index') }}">{{ __('User approvals') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Account lifecycle') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Account lifecycle') }}</flux:heading>
            <flux:text>{{ __('Suspend accounts when access must be revoked; reactivation does not restore old sessions. Platform-admin accounts cannot be changed here.') }}</flux:text>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif

        <section class="space-y-3 md:hidden" aria-label="{{ __('Accounts') }}">
            @forelse ($users as $user)
                <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="break-words font-medium">{{ $user->name }}</h2><p class="mt-1 break-all text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }}</p></div><span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-zinc-800">{{ __(str($user->status)->title()->toString()) }}</span></div>
                    <form method="POST" action="{{ route($user->status === 'active' ? 'admin.users.suspend' : 'admin.users.reactivate', $user) }}" class="grid gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        @csrf
                        <label class="text-sm font-medium" for="mobile_reason-{{ $user->id }}">{{ __('Reason') }}</label>
                        <input id="mobile_reason-{{ $user->id }}" name="reason" maxlength="500" required class="min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                        @error('reason')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <flux:button class="w-full" type="submit" variant="{{ $user->status === 'active' ? 'danger' : 'primary' }}">{{ $user->status === 'active' ? __('Suspend') : __('Reactivate') }}</flux:button>
                    </form>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ __('No active or suspended non-admin accounts.') }}</p>
            @endforelse
        </section>
        <section class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block" aria-label="{{ __('Accounts') }}">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-zinc-200 dark:border-zinc-700"><th class="p-3">{{ __('Name') }}</th><th class="p-3">{{ __('Email') }}</th><th class="p-3">{{ __('Status') }}</th><th class="p-3">{{ __('Action') }}</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-zinc-100 align-top dark:border-zinc-800">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ __(str($user->status)->title()->toString()) }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route($user->status === 'active' ? 'admin.users.suspend' : 'admin.users.reactivate', $user) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-xs font-medium" for="reason-{{ $user->id }}">{{ __('Reason') }}</label>
                                        <input id="reason-{{ $user->id }}" name="reason" maxlength="500" required class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                        @error('reason')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                                    </div>
                                    <flux:button type="submit" variant="{{ $user->status === 'active' ? 'danger' : 'primary' }}">{{ $user->status === 'active' ? __('Suspend') : __('Reactivate') }}</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-zinc-600 dark:text-zinc-300">{{ __('No active or suspended non-admin accounts.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        {{ $users->links() }}

        <section class="space-y-3" aria-labelledby="account-events-heading">
            <flux:heading id="account-events-heading" size="lg">{{ __('Recent account access events') }}</flux:heading>
            <div class="space-y-3 md:hidden">
                @forelse ($events as $event)
                    <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3"><h3 class="font-medium">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}</h3><time class="shrink-0 text-xs text-zinc-500">{{ $event->created_at }}</time></div>
                        <dl class="grid grid-cols-2 gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700"><div><dt class="text-zinc-500">{{ __('Actor') }}</dt><dd class="mt-1 break-words">{{ $event->actor_name }}</dd></div><div><dt class="text-zinc-500">{{ __('Account') }}</dt><dd class="mt-1 break-words">{{ $event->target_name }}</dd></div><div class="col-span-2"><dt class="text-zinc-500">{{ __('Reason') }}</dt><dd class="mt-1 break-words">{{ $event->reason }}</dd></div></dl>
                    </article>
                @empty
                    <p class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ __('No account lifecycle events.') }}</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b border-zinc-200 dark:border-zinc-700"><th class="p-3">{{ __('Time') }}</th><th class="p-3">{{ __('Actor') }}</th><th class="p-3">{{ __('Account') }}</th><th class="p-3">{{ __('Event') }}</th><th class="p-3">{{ __('Reason') }}</th></tr></thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800"><td class="p-3">{{ $event->created_at }}</td><td class="p-3">{{ $event->actor_name }}</td><td class="p-3">{{ $event->target_name }}</td><td class="p-3">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}</td><td class="p-3">{{ $event->reason }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-zinc-600 dark:text-zinc-300">{{ __('No account lifecycle events.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
