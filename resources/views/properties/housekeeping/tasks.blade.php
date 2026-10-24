<x-layouts::app :title="__('Housekeeping tasks')">
    <div class="space-y-6">
        <header class="space-y-2">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('my.properties.index') }}">{{ __('My properties') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('properties.housekeeping.board', $property) }}">{{ $property->name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Tasks') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Housekeeping tasks') }}</flux:heading>
            <flux:text>{{ $property->name }} · {{ __('Completing a task does not change room status.') }}</flux:text>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-lg border border-emerald-300 p-3 text-sm text-emerald-900 dark:border-emerald-800 dark:text-emerald-100">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-3 font-semibold">{{ __('Create task') }}</h2>
            <form method="POST" action="{{ route('properties.housekeeping.tasks.store', $property) }}" class="grid gap-3 md:grid-cols-4">
                @csrf
                <label class="grid gap-1 text-sm">{{ __('Room') }}
                    <select name="room_id" required class="min-h-11 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600">
                        <option value="">{{ __('Select room') }}</option>
                        @foreach ($rooms as $room)<option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->code }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">{{ __('Assign to (optional)') }}
                    <select name="assigned_to" class="min-h-11 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach ($staff as $person)<option value="{{ $person->id }}" @selected(old('assigned_to') == $person->id)>{{ $person->name }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm md:col-span-2">{{ __('Instruction (optional)') }}
                    <input name="instruction" value="{{ old('instruction') }}" maxlength="500" class="min-h-11 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600">
                </label>
                <div class="md:col-span-4"><flux:button type="submit" variant="primary">{{ __('Create task') }}</flux:button></div>
            </form>
        </section>

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="grid gap-1 text-sm" for="task_status">{{ __('Filter status') }}
                <select id="task_status" name="status" class="min-h-11 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['open', 'in_progress', 'completed', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>@endforeach
                </select>
            </label>
            <flux:button type="submit">{{ __('Apply filter') }}</flux:button>
            <a class="inline-flex min-h-11 items-center px-2 text-sm underline" href="{{ route('properties.housekeeping.tasks.index', $property) }}">{{ __('Clear') }}</a>
        </form>

        <div class="space-y-3 md:hidden">
            @forelse ($tasks as $task)
                <article class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex justify-between gap-3"><div><h2 class="font-semibold">{{ __('Room :room', ['room' => $task->code]) }}</h2><p class="text-sm text-zinc-500">{{ $task->created_at }}</p></div><span class="rounded-full bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">{{ __(ucfirst(str_replace('_', ' ', $task->status))) }}</span></div>
                    @if ($task->instruction)<p class="break-words text-sm">{{ $task->instruction }}</p>@endif
                    <form method="POST" action="{{ route('properties.housekeeping.tasks.assign', [$property, $task->id]) }}" class="grid gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">@csrf @method('PATCH')
                        <label class="text-sm">{{ __('Assignee') }}<select name="assigned_to" class="mt-1 min-h-11 w-full rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600"><option value="">{{ __('Unassigned') }}</option>@foreach ($staff as $person)<option value="{{ $person->id }}" @selected($task->assigned_to == $person->id)>{{ $person->name }}</option>@endforeach</select></label>
                        @if ($task->status === 'open')<flux:button type="submit">{{ __('Save assignment') }}</flux:button>@endif
                    </form>
                    <div class="flex flex-wrap gap-2">
                        @if ($task->status === 'open')<form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'start']) }}">@csrf @method('PATCH')<flux:button type="submit">{{ __('Start') }}</flux:button></form>@endif
                        @if ($task->status === 'in_progress')<form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'complete']) }}">@csrf @method('PATCH')<flux:button type="submit" variant="primary">{{ __('Complete') }}</flux:button></form>@endif
                        @if (in_array($task->status, ['open', 'in_progress'], true))<form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'cancel']) }}" class="flex flex-wrap gap-2">@csrf @method('PATCH')<label class="sr-only" for="cancel_mobile_{{ $task->id }}">{{ __('Cancellation reason') }}</label><input id="cancel_mobile_{{ $task->id }}" name="reason" required maxlength="500" placeholder="{{ __('Cancellation reason') }}" class="min-h-11 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600"><flux:button type="submit">{{ __('Cancel task') }}</flux:button></form>@endif
                    </div>
                    <details><summary class="cursor-pointer text-sm underline">{{ __('History') }}</summary><ol class="mt-2 space-y-2 text-sm">@foreach ($eventsByTask[$task->id] ?? [] as $event)<li>{{ $event->created_at }} · {{ $event->actor_name }} · {{ __(ucfirst(str_replace('_', ' ', $event->event_type))) }}@if ($event->reason) — {{ $event->reason }}@endif</li>@endforeach</ol></details>
                </article>
            @empty
                <p class="rounded-xl border border-zinc-200 p-8 text-center text-zinc-500 dark:border-zinc-700">{{ __('No housekeeping tasks found.') }}</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 md:block">
            <table class="w-full text-start text-sm"><caption class="sr-only">{{ __('Housekeeping task queue') }} — {{ $property->name }}</caption>
                <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700"><tr><th scope="col" class="px-4 py-3">{{ __('Room') }}</th><th scope="col" class="px-4 py-3">{{ __('Task') }}</th><th scope="col" class="px-4 py-3">{{ __('Status') }}</th><th scope="col" class="px-4 py-3">{{ __('Assignee') }}</th><th scope="col" class="px-4 py-3">{{ __('Actions') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($tasks as $task)<tr><td class="px-4 py-3">{{ $task->code }}</td><td class="max-w-xs break-words px-4 py-3">{{ $task->instruction }}</td><td class="px-4 py-3">{{ __(ucfirst(str_replace('_', ' ', $task->status))) }}</td><td class="px-4 py-3">{{ $task->name ?? __('Unassigned') }}</td><td class="space-y-2 px-4 py-3">
                    @if ($task->status === 'open')<form method="POST" action="{{ route('properties.housekeeping.tasks.assign', [$property, $task->id]) }}" class="flex flex-wrap gap-2">@csrf @method('PATCH')<label class="sr-only" for="assignee_{{ $task->id }}">{{ __('Assignee') }}</label><select id="assignee_{{ $task->id }}" name="assigned_to" class="min-h-11 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600"><option value="">{{ __('Unassigned') }}</option>@foreach ($staff as $person)<option value="{{ $person->id }}" @selected($task->assigned_to == $person->id)>{{ $person->name }}</option>@endforeach</select><flux:button type="submit">{{ __('Save assignment') }}</flux:button></form><form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'start']) }}">@csrf @method('PATCH')<flux:button type="submit">{{ __('Start') }}</flux:button></form>@endif
                    @if ($task->status === 'in_progress')<form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'complete']) }}">@csrf @method('PATCH')<flux:button type="submit" variant="primary">{{ __('Complete') }}</flux:button></form>@endif
                    @if (in_array($task->status, ['open', 'in_progress'], true))<form method="POST" action="{{ route('properties.housekeeping.tasks.transition', [$property, $task->id, 'cancel']) }}" class="flex gap-2">@csrf @method('PATCH')<label class="sr-only" for="cancel_{{ $task->id }}">{{ __('Cancellation reason') }}</label><input id="cancel_{{ $task->id }}" name="reason" required maxlength="500" class="min-h-11 min-w-0 rounded-lg border border-zinc-300 bg-transparent px-3 dark:border-zinc-600"><flux:button type="submit">{{ __('Cancel task') }}</flux:button></form>@endif
                    <details><summary class="cursor-pointer underline">{{ __('History') }}</summary><ol class="mt-2 space-y-1">@foreach ($eventsByTask[$task->id] ?? [] as $event)<li>{{ $event->created_at }} · {{ $event->actor_name }} · {{ $event->event_type }}@if ($event->reason) — {{ $event->reason }}@endif</li>@endforeach</ol></details>
                </td></tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('No housekeeping tasks found.') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $tasks->links() }}
    </div>
</x-layouts::app>
