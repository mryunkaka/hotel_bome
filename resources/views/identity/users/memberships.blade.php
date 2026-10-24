<x-layouts::app :title="__('Property access')">
    <div class="space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('admin.users.index') }}">{{ __('User approvals') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Property access') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Property access') }}</flux:heading>
            <flux:text>{{ __('Manage active-user property memberships. Every change requires a reason and is audited.') }}</flux:text>
            <div role="note" class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">{{ __('Role profiles do not grant a complete module permission set. Membership currently grants only the exact routes documented in IAM-D3; other roles remain denied property routes.') }}</div>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="space-y-5">
            @foreach ($users as $user)
                <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" aria-labelledby="user-{{ $user->id }}">
                    <div>
                        <h2 id="user-{{ $user->id }}" class="font-semibold">{{ $user->name }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }} @unless ($user->status === 'active') — {{ __('Inactive account') }} @endunless</p>
                    </div>

                    <div class="space-y-3">
                        @forelse ($memberships->get($user->id, collect()) as $membership)
                            <div class="flex flex-wrap items-end justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium">{{ $membership->property_name }} ({{ $membership->property_code }})</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __(str($membership->role)->replace('_', ' ')->title()->toString()) }} @unless ($membership->property_is_active) — {{ __('Inactive property') }} @endunless</p>
                                </div>
                                @if ($membership->property_is_active && $user->status === 'active' && ! $user->is_platform_admin)
                                    <form method="POST" action="{{ route('admin.users.memberships.store', $user) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $membership->property_id }}">
                                        <div>
                                            <label class="sr-only" for="role-{{ $user->id }}-{{ $membership->property_id }}">{{ __('Role for :user at :property', ['user' => $user->name, 'property' => $membership->property_name]) }}</label>
                                            <select id="role-{{ $user->id }}-{{ $membership->property_id }}" name="role" required class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role }}" @selected($membership->role === $role)>{{ __(str($role)->replace('_', ' ')->title()->toString()) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label class="sr-only" for="reason-{{ $user->id }}-{{ $membership->property_id }}">{{ __('Reason') }}</label>
                                        <input id="reason-{{ $user->id }}-{{ $membership->property_id }}" name="reason" maxlength="500" required placeholder="{{ __('Reason') }}" class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                                        <flux:button type="submit">{{ __('Save role') }}</flux:button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.users.memberships.destroy', [$user, $membership->property_id]) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    @method('DELETE')
                                    <label class="sr-only" for="revoke-reason-{{ $user->id }}-{{ $membership->property_id }}">{{ __('Revocation reason') }}</label>
                                    <input id="revoke-reason-{{ $user->id }}-{{ $membership->property_id }}" name="reason" maxlength="500" required placeholder="{{ __('Revocation reason') }}" class="rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-600">
                                    <flux:button type="submit" variant="danger">{{ __('Revoke') }}</flux:button>
                                </form>
                            </div>
                        @empty
                            <p class="border-t border-zinc-200 pt-3 text-sm text-zinc-500 dark:border-zinc-700">{{ __('No property memberships.') }}</p>
                        @endforelse
                    </div>

                    @if ($user->status === 'active' && ! $user->is_platform_admin)
                    <form method="POST" action="{{ route('admin.users.memberships.store', $user) }}" class="grid gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:grid-cols-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="new-property-{{ $user->id }}">{{ __('Add active property') }}</label>
                            <select id="new-property-{{ $user->id }}" name="property_id" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                <option value="">{{ __('Select property') }}</option>
                                @foreach ($properties->whereNotIn('id', $memberships->get($user->id, collect())->pluck('property_id')) as $property)
                                    <option value="{{ $property->id }}">{{ $property->name }} ({{ $property->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="new-role-{{ $user->id }}">{{ __('Role profile') }}</label>
                            <select id="new-role-{{ $user->id }}" name="role" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                                <option value="">{{ __('Select role') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}">{{ __(str($role)->replace('_', ' ')->title()->toString()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="new-reason-{{ $user->id }}">{{ __('Reason') }}</label>
                            <input id="new-reason-{{ $user->id }}" name="reason" maxlength="500" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                        </div>
                        <div class="flex items-end"><flux:button type="submit" variant="primary">{{ __('Add membership') }}</flux:button></div>
                    </form>
                    @endif
                </section>
            @endforeach
        </div>

        {{ $users->links() }}

        <section class="space-y-4" aria-labelledby="membership-history">
            <flux:heading id="membership-history" size="lg">{{ __('Recent membership audit') }}</flux:heading>
            <div class="space-y-3 md:hidden">
                @forelse ($events as $event)
                    <article class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3"><h3 class="font-medium">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}: {{ $event->previous_role ?? '—' }} → {{ $event->role ?? '—' }}</h3><time class="shrink-0 text-xs text-zinc-500">{{ $event->created_at }}</time></div>
                        <dl class="grid grid-cols-2 gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700"><div><dt class="text-zinc-500">{{ __('Actor') }}</dt><dd class="mt-1 break-words">{{ $event->actor_name }}</dd></div><div><dt class="text-zinc-500">{{ __('User') }}</dt><dd class="mt-1 break-words">{{ $event->target_name }}</dd></div><div><dt class="text-zinc-500">{{ __('Property') }}</dt><dd class="mt-1 break-words">{{ $event->property_name }}</dd></div><div class="col-span-2"><dt class="text-zinc-500">{{ __('Reason') }}</dt><dd class="mt-1 break-words">{{ $event->reason }}</dd></div></dl>
                    </article>
                @empty
                    <p class="rounded-xl border border-zinc-200 bg-white p-4 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">{{ __('No membership events yet.') }}</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
                <table class="w-full text-start text-sm">
                    <caption class="sr-only">{{ __('Latest membership changes and reasons') }}</caption>
                    <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700"><tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('When') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Actor') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('User') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Property') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Change') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Reason') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($events as $event)
                            <tr>
                                <td class="px-4 py-3">{{ $event->created_at }}</td>
                                <td class="px-4 py-3">{{ $event->actor_name }}</td>
                                <td class="px-4 py-3">{{ $event->target_name }}</td>
                                <td class="px-4 py-3">{{ $event->property_name }}</td>
                                <td class="px-4 py-3">{{ __(str($event->event)->replace('_', ' ')->title()->toString()) }}: {{ $event->previous_role ?? '—' }} → {{ $event->role ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $event->reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">{{ __('No membership events yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
