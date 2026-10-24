<x-layouts::app :title="__('User approvals')">
    <div class="space-y-8">
        <div class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ config('app.name', 'Hotel Bome') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('User approvals') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('User approvals') }}</flux:heading>
            <flux:text>{{ __('Review pending registrations. Approval requires one active property, a role profile, and a reason.') }}</flux:text>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">{{ session('status') }}</div>
        @endif

        @if ($pendingUsers->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ __('No pending registrations.') }}</div>
        @endif

        @foreach ($pendingUsers as $user)
            <section class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" aria-labelledby="user-{{ $user->id }}">
                <div>
                    <h2 id="user-{{ $user->id }}" class="font-semibold">{{ $user->name }}</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Registered') }}: {{ $user->created_at?->toDateTimeString() }}</p>
                </div>

                <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="grid gap-4 md:grid-cols-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="property-{{ $user->id }}">{{ __('Property') }}</label>
                        <select id="property-{{ $user->id }}" name="property_id" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                            <option value="">{{ __('Select property') }}</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>{{ $property->name }} ({{ $property->code }})</option>
                            @endforeach
                        </select>
                        @error('property_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="role-{{ $user->id }}">{{ __('Role profile') }}</label>
                        <select id="role-{{ $user->id }}" name="role" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                            <option value="">{{ __('Select role') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ __(str($role)->replace('_', ' ')->title()->toString()) }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="approve-reason-{{ $user->id }}">{{ __('Reason') }}</label>
                        <input id="approve-reason-{{ $user->id }}" name="reason" maxlength="500" required value="{{ old('reason') }}" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                        @error('reason')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end">
                        <flux:button type="submit" variant="primary">{{ __('Approve and assign') }}</flux:button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="flex flex-wrap items-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    @csrf
                    <div class="min-w-64 flex-1">
                        <label class="mb-1 block text-sm font-medium" for="reject-reason-{{ $user->id }}">{{ __('Rejection reason') }}</label>
                        <input id="reject-reason-{{ $user->id }}" name="reason" maxlength="500" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                    </div>
                    <flux:button type="submit" variant="danger">{{ __('Reject registration') }}</flux:button>
                </form>
            </section>
        @endforeach

        {{ $pendingUsers->links() }}
    </div>
</x-layouts::app>
