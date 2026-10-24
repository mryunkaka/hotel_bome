<?php

namespace App\Http\Controllers\Audit;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuditHistoryController
{
    private const PAGE_SIZE = 50;

    private const SOURCES = [
        'user_access',
        'membership',
        'account',
        'room_status',
        'room_block_cancellation',
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'source' => ['nullable', Rule::in(self::SOURCES)],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from'],
        ]);

        $from = isset($filters['from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], config('app.timezone'))
            : null;
        $to = isset($filters['to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], config('app.timezone'))
            : null;

        if ($from && $to && ($from->greaterThan($to) || $from->diffInDays($to) > 365)) {
            throw ValidationException::withMessages(['to' => __('Choose a date range no longer than 366 calendar days.')]);
        }

        $queries = $this->sourceQueries();
        if (isset($filters['source'])) {
            $queries = [$filters['source'] => $queries[$filters['source']]];
        }

        foreach ($queries as $source => $query) {
            if (isset($filters['property_id'])) {
                if ($source === 'account') {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('e.property_id', $filters['property_id']);
                }
            }
            if ($from) {
                $query->where('e.created_at', '>=', $from->startOfDay()->toDateTimeString());
            }
            if ($to) {
                $query->where('e.created_at', '<=', $to->endOfDay()->toDateTimeString());
            }
        }

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        $events = DB::query()->fromSub($union, 'audit_events');

        return view('audit.history', [
            'events' => $events
                ->orderByDesc('occurred_at')
                ->orderBy('source')
                ->orderByDesc('source_id')
                ->simplePaginate(self::PAGE_SIZE)
                ->appends($filters),
            'filters' => $filters,
            'properties' => DB::table('properties')->orderBy('name')->get(['id', 'name']),
            'sources' => self::SOURCES,
        ]);
    }

    /** @return array<string, Builder> */
    private function sourceQueries(): array
    {
        return [
            'user_access' => DB::table('user_access_events as e')
                ->join('users as a', 'a.id', '=', 'e.actor_id')
                ->join('users as s', 's.id', '=', 'e.user_id')
                ->leftJoin('properties as p', 'p.id', '=', 'e.property_id')
                ->selectRaw("'user_access' as source, e.id as source_id, e.created_at as occurred_at, e.event, e.property_id, p.name as property_name, a.name as actor_name, s.name as subject_name, NULL as previous_value, e.role as new_value, e.reason"),
            'membership' => DB::table('property_membership_events as e')
                ->join('users as a', 'a.id', '=', 'e.actor_id')
                ->join('users as s', 's.id', '=', 'e.user_id')
                ->join('properties as p', 'p.id', '=', 'e.property_id')
                ->selectRaw("'membership' as source, e.id as source_id, e.created_at as occurred_at, e.event, e.property_id, p.name as property_name, a.name as actor_name, s.name as subject_name, e.previous_role as previous_value, e.role as new_value, e.reason"),
            'account' => DB::table('user_account_events as e')
                ->join('users as a', 'a.id', '=', 'e.actor_id')
                ->join('users as s', 's.id', '=', 'e.user_id')
                ->selectRaw("'account' as source, e.id as source_id, e.created_at as occurred_at, e.event, NULL as property_id, NULL as property_name, a.name as actor_name, s.name as subject_name, NULL as previous_value, NULL as new_value, e.reason"),
            'room_status' => DB::table('room_status_events as e')
                ->join('rooms as r', function ($join): void {
                    $join->on('r.id', '=', 'e.room_id')->on('r.property_id', '=', 'e.property_id');
                })
                ->join('properties as p', 'p.id', '=', 'e.property_id')
                ->leftJoin('users as a', 'a.id', '=', 'e.actor_id')
                ->selectRaw("'room_status' as source, e.id as source_id, e.created_at as occurred_at, 'status_changed' as event, e.property_id, p.name as property_name, a.name as actor_name, r.code as subject_name, e.previous_status as previous_value, e.status as new_value, e.reason"),
            'room_block_cancellation' => DB::table('room_block_cancellations as e')
                ->join('rooms as r', function ($join): void {
                    $join->on('r.id', '=', 'e.room_id')->on('r.property_id', '=', 'e.property_id');
                })
                ->join('properties as p', 'p.id', '=', 'e.property_id')
                ->join('users as a', 'a.id', '=', 'e.actor_id')
                ->selectRaw("'room_block_cancellation' as source, e.id as source_id, e.created_at as occurred_at, 'cancelled' as event, e.property_id, p.name as property_name, a.name as actor_name, r.code as subject_name, NULL as previous_value, NULL as new_value, e.reason"),
        ];
    }
}
