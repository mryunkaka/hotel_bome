<?php

namespace App\Http\Controllers\Properties;

use App\Enums\HousekeepingStatus;
use App\Models\Property;
use App\Services\Properties\RoomOperations;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyInventoryController
{
    public function outlets(Property $property): View
    {
        return $this->index($property, 'outlet');
    }

    public function roomTypes(Property $property): View
    {
        return $this->index($property, 'room_type');
    }

    public function rooms(Request $request, Property $property): View
    {
        return $this->roomBoard($request, $property);
    }

    public function housekeepingBoard(Property $property): View
    {
        abort_if(! $property->is_active, 404);

        $rooms = DB::table('rooms')
            ->join('room_types', function ($join) use ($property): void {
                $join->on('room_types.id', '=', 'rooms.room_type_id')
                    ->where('room_types.property_id', '=', $property->id);
            })
            ->where('rooms.property_id', $property->id)
            ->where('rooms.is_active', true)
            ->select('rooms.id', 'rooms.code', 'room_types.name as room_type_name')
            ->selectSub(
                DB::table('room_status_events')
                    ->select('status')
                    ->whereColumn('room_status_events.property_id', 'rooms.property_id')
                    ->whereColumn('room_status_events.room_id', 'rooms.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'housekeeping_status',
            )
            ->orderBy('rooms.code')
            ->paginate(20);

        return view('properties.housekeeping.board', [
            'property' => $property,
            'rooms' => $rooms,
        ]);
    }

    public function roomHistory(Property $property): View
    {
        $statusEvents = DB::table('room_status_events')
            ->where('property_id', $property->id)
            ->select([
                'id',
                'property_id',
                'room_id',
                DB::raw("'housekeeping_status' as event_type"),
                'previous_status',
                'status',
                DB::raw('NULL as start_date'),
                DB::raw('NULL as end_date'),
                'reason',
                'actor_id',
                'created_at',
            ]);
        $blockEvents = DB::table('room_blocks')
            ->where('property_id', $property->id)
            ->select([
                'id',
                'property_id',
                'room_id',
                DB::raw("'room_block_added' as event_type"),
                DB::raw('NULL as previous_status'),
                DB::raw('NULL as status'),
                'start_date',
                'end_date',
                'reason',
                'created_by as actor_id',
                'created_at',
            ]);
        $cancellationEvents = DB::table('room_block_cancellations')
            ->where('room_block_cancellations.property_id', $property->id)
            ->join('room_blocks', function ($join): void {
                $join->on('room_blocks.id', '=', 'room_block_cancellations.room_block_id')
                    ->on('room_blocks.property_id', '=', 'room_block_cancellations.property_id')
                    ->on('room_blocks.room_id', '=', 'room_block_cancellations.room_id');
            })
            ->select([
                'room_block_cancellations.id',
                'room_block_cancellations.property_id',
                'room_block_cancellations.room_id',
                DB::raw("'room_block_cancelled' as event_type"),
                DB::raw('NULL as previous_status'),
                DB::raw('NULL as status'),
                'room_blocks.start_date',
                'room_blocks.end_date',
                'room_block_cancellations.reason',
                'room_block_cancellations.actor_id',
                'room_block_cancellations.created_at',
            ]);
        $timeline = $statusEvents->unionAll($blockEvents)->unionAll($cancellationEvents);

        $events = DB::query()
            ->fromSub($timeline, 'timeline')
            ->join('rooms', function ($join): void {
                $join->on('rooms.id', '=', 'timeline.room_id')
                    ->on('rooms.property_id', '=', 'timeline.property_id');
            })
            ->where('timeline.property_id', $property->id)
            ->select('timeline.*', 'rooms.code as room_code')
            ->orderByDesc('timeline.created_at')
            ->orderByDesc('timeline.id')
            ->orderBy('timeline.event_type')
            ->paginate(30)
            ->withQueryString();

        return view('properties.inventory.history', [
            'property' => $property,
            'events' => $events,
        ]);
    }

    public function updateRoomStatus(Request $request, RoomOperations $operations, Property $property, int $room): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(HousekeepingStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $operations->changeHousekeepingStatus(
                $property->id,
                $room,
                HousekeepingStatus::from($data['status']),
                $request->user(),
                $data['reason'] ?? null,
            );
        } catch (\DomainException) {
            return back()->withErrors(['status' => __('Invalid housekeeping status transition.')])->withInput();
        }

        return redirect()->route('properties.rooms.index', $property);
    }

    public function updateHousekeepingStatus(Request $request, RoomOperations $operations, Property $property, int $room): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(HousekeepingStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $operations->changeHousekeepingStatus(
                $property->id,
                $room,
                HousekeepingStatus::from($data['status']),
                $request->user(),
                $data['reason'] ?? null,
            );
        } catch (\DomainException) {
            return back()->withErrors(['status' => __('Invalid housekeeping status transition.')])->withInput();
        }

        return redirect()->route('properties.housekeeping.board', $property);
    }

    public function storeRoomBlock(Request $request, Property $property, RoomOperations $operations): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('property_id', $property->id)->where('is_active', true)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'regex:/\\S/', 'max:500'],
        ]);

        $operations->createBlock(
            $property->id,
            (int) $data['room_id'],
            CarbonImmutable::createFromFormat('!Y-m-d', $data['start_date']),
            CarbonImmutable::createFromFormat('!Y-m-d', $data['end_date']),
            $data['reason'],
            $request->user(),
        );

        return redirect()->route('properties.rooms.index', $property);
    }

    public function cancelRoomBlock(Request $request, Property $property, int $room, int $block, RoomOperations $operations): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'regex:/\\S/', 'max:500']]);
        $operations->cancelBlock($property->id, $room, $block, $data['reason'], $request->user());

        return redirect()->route('properties.rooms.index', $property);
    }

    public function storeOutlet(Request $request, Property $property): RedirectResponse
    {
        return DB::transaction(function () use ($request, $property): RedirectResponse {
            $this->lockActiveProperty($property);
            $data = $this->nameAndCode($request, 'outlets', $property);
            DB::table('outlets')->insert(['property_id' => $property->id, ...$data, 'is_active' => true]);

            return redirect()->route('properties.outlets.index', $property);
        });
    }

    public function storeRoomType(Request $request, Property $property): RedirectResponse
    {
        return DB::transaction(function () use ($request, $property): RedirectResponse {
            $this->lockActiveProperty($property);
            $data = $this->nameAndCode($request, 'room_types', $property);
            DB::table('room_types')->insert(['property_id' => $property->id, ...$data, 'is_active' => true]);

            return redirect()->route('properties.room-types.index', $property);
        });
    }

    public function storeRoom(Request $request, Property $property): RedirectResponse
    {
        abort_if(! $property->is_active, 404);
        $this->normalizeCode($request);

        return DB::transaction(function () use ($request, $property): RedirectResponse {
            $this->lockActiveProperty($property);
            $data = $request->validate([
                'room_type_id' => [
                    'required',
                    'integer',
                    Rule::exists('room_types', 'id')
                        ->where('property_id', $property->id)
                        ->where('is_active', true),
                ],
                'code' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D',
                    Rule::unique('rooms', 'code')->where('property_id', $property->id),
                ],
            ]);

            $roomTypeId = (int) $data['room_type_id'];
            $roomType = DB::table('room_types')
                ->where('property_id', $property->id)
                ->where('id', $roomTypeId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            abort_if($roomType === null, 404);

            $roomId = DB::table('rooms')->insertGetId([
                'property_id' => $property->id,
                'room_type_id' => $data['room_type_id'],
                'code' => $data['code'],
                'is_active' => true,
            ]);
            DB::table('room_status_events')->insert([
                'property_id' => $property->id,
                'room_id' => $roomId,
                'previous_status' => null,
                'status' => 'dirty',
                'actor_id' => $request->user()?->id,
                'created_at' => now(),
            ]);

            return redirect()->route('properties.rooms.index', $property);
        });
    }

    public function editOutlet(Property $property, int $outlet): View
    {
        return $this->edit($property, 'outlets', $outlet, 'outlet');
    }

    public function editRoomType(Property $property, int $roomType): View
    {
        return $this->edit($property, 'room_types', $roomType, 'room_type');
    }

    public function editRoom(Property $property, int $room): View
    {
        return $this->edit($property, 'rooms', $room, 'room');
    }

    public function updateOutlet(Request $request, Property $property, int $outlet): RedirectResponse
    {
        return $this->update($request, $property, 'outlets', $outlet, 'outlet', 'properties.outlets.index');
    }

    public function updateRoomType(Request $request, Property $property, int $roomType): RedirectResponse
    {
        return $this->update($request, $property, 'room_types', $roomType, 'room_type', 'properties.room-types.index');
    }

    public function updateRoom(Request $request, Property $property, int $room): RedirectResponse
    {
        return $this->update($request, $property, 'rooms', $room, 'room', 'properties.rooms.index');
    }

    public function deactivateOutlet(Property $property, int $outlet): RedirectResponse
    {
        return $this->deactivate($property, 'outlets', $outlet, 'properties.outlets.index');
    }

    public function deactivateRoomType(Property $property, int $roomType): RedirectResponse
    {
        return DB::transaction(function () use ($property, $roomType): RedirectResponse {
            $this->lockActiveProperty($property);
            $activeRoomType = DB::table('room_types')
                ->where('property_id', $property->id)
                ->where('id', $roomType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            abort_if($activeRoomType === null, 404);

            abort_if(DB::table('rooms')
                ->where('property_id', $property->id)
                ->where('room_type_id', $roomType)
                ->where('is_active', true)
                ->exists(), 409);

            return $this->deactivate($property, 'room_types', $roomType, 'properties.room-types.index');
        });
    }

    public function deactivateRoom(Property $property, int $room): RedirectResponse
    {
        return $this->deactivate($property, 'rooms', $room, 'properties.rooms.index');
    }

    private function edit(Property $property, string $table, int $id, string $kind): View
    {
        abort_if(! $property->is_active, 404);
        $query = DB::table($table)
            ->where("{$table}.property_id", $property->id)
            ->where("{$table}.id", $id)
            ->where("{$table}.is_active", true);

        if ($kind === 'room') {
            $query->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
                ->select('rooms.*', 'room_types.name as room_type_name');
        }

        $item = $query->first();
        abort_if($item === null, 404);

        return view('properties.inventory.edit', ['property' => $property, 'item' => $item, 'kind' => $kind]);
    }

    private function update(Request $request, Property $property, string $table, int $id, string $kind, string $indexRoute): RedirectResponse
    {
        abort_if(! $property->is_active, 404);
        $this->normalizeCode($request);
        $rules = [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D',
                Rule::unique($table, 'code')->where('property_id', $property->id)->ignore($id),
            ],
        ];

        if ($kind !== 'room') {
            $rules['name'] = ['required', 'string', 'max:120'];
        }

        return DB::transaction(function () use ($request, $property, $table, $id, $rules, $indexRoute): RedirectResponse {
            $this->lockActiveProperty($property);
            $data = $request->validate($rules);
            $updated = DB::table($table)
                ->where('property_id', $property->id)
                ->where('id', $id)
                ->where('is_active', true)
                ->update([...$data, 'updated_at' => now()]);
            abort_if($updated !== 1, 404);

            return redirect()->route($indexRoute, $property);
        });
    }

    private function deactivate(Property $property, string $table, int $id, string $route): RedirectResponse
    {
        return DB::transaction(function () use ($property, $table, $id, $route): RedirectResponse {
            $this->lockActiveProperty($property);
            $updated = DB::table($table)
                ->where('property_id', $property->id)
                ->where('id', $id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);
            abort_if($updated !== 1, 404);

            return redirect()->route($route, $property);
        });
    }

    private function lockActiveProperty(Property $property): void
    {
        $activeProperty = DB::table('properties')
            ->where('id', $property->id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        abort_if($activeProperty === null, 404);
    }

    private function roomBoard(Request $request, Property $property): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:40'],
            'housekeeping_status' => ['nullable', 'in:all,dirty,clean,inspected'],
            'active' => ['nullable', 'in:all,active,inactive'],
        ]);

        $query = DB::table('rooms')
            ->join('room_types', function ($join) use ($property): void {
                $join->on('rooms.room_type_id', '=', 'room_types.id')
                    ->where('room_types.property_id', '=', $property->id);
            })
            ->where('rooms.property_id', $property->id)
            ->select('rooms.*', 'room_types.name as room_type_name')
            ->selectSub(
                DB::table('room_status_events')
                    ->select('status')
                    ->whereColumn('room_status_events.property_id', 'rooms.property_id')
                    ->whereColumn('room_status_events.room_id', 'rooms.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'housekeeping_status',
            );

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where('rooms.code', 'like', '%'.$search.'%');
        }

        $status = $filters['housekeeping_status'] ?? 'all';
        if ($status !== 'all') {
            $query->whereExists(function ($subquery) use ($status): void {
                $subquery->selectRaw('1')
                    ->from('room_status_events as latest_status')
                    ->whereColumn('latest_status.property_id', 'rooms.property_id')
                    ->whereColumn('latest_status.room_id', 'rooms.id')
                    ->where('latest_status.status', $status)
                    ->where('latest_status.id', function ($latest): void {
                        $latest->selectRaw('MAX(room_status_events.id)')
                            ->from('room_status_events')
                            ->whereColumn('room_status_events.room_id', 'latest_status.room_id')
                            ->whereColumn('room_status_events.property_id', 'latest_status.property_id');
                    });
            });
        }

        $active = $filters['active'] ?? 'all';
        if ($active === 'active') {
            $query->where('rooms.is_active', true);
        } elseif ($active === 'inactive') {
            $query->where('rooms.is_active', false);
        }

        $items = $query->orderBy('rooms.code')->paginate(20)->withQueryString();
        $roomIds = $items->getCollection()->pluck('id');
        $blocksByRoom = DB::table('room_blocks')
            ->where('property_id', $property->id)
            ->whereIn('room_id', $roomIds)
            ->whereNotExists(function ($subquery): void {
                $subquery->selectRaw('1')
                    ->from('room_block_cancellations')
                    ->whereColumn('room_block_cancellations.room_block_id', 'room_blocks.id');
            })
            ->orderBy('start_date')
            ->get()
            ->groupBy('room_id');
        $activeRooms = DB::table('rooms')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code']);
        $roomTypes = DB::table('room_types')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('properties.inventory.index', [
            'property' => $property,
            'kind' => 'room',
            'items' => $items,
            'roomTypes' => $roomTypes,
            'roomTypesWithActiveRooms' => collect(),
            'activeRooms' => $activeRooms,
            'blocksByRoom' => $blocksByRoom,
            'search' => $search,
            'statusFilter' => is_string($status) && HousekeepingStatus::tryFrom($status) !== null ? $status : 'all',
            'activeFilter' => $active,
        ]);
    }

    private function index(Property $property, string $kind): View
    {
        $table = match ($kind) {
            'outlet' => 'outlets',
            'room_type' => 'room_types',
            default => 'rooms',
        };
        $query = DB::table($table)->where("{$table}.property_id", $property->id);

        if ($kind === 'room') {
            $query->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
                ->select('rooms.*', 'room_types.name as room_type_name');
        }

        $roomTypes = DB::table('room_types')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $roomTypesWithActiveRooms = DB::table('rooms')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->pluck('room_type_id');

        return view('properties.inventory.index', [
            'property' => $property,
            'kind' => $kind,
            'items' => $query->orderBy('code')->paginate(20),
            'roomTypes' => $roomTypes,
            'roomTypesWithActiveRooms' => $roomTypesWithActiveRooms,
        ]);
    }

    /** @return array{name: string, code: string} */
    private function nameAndCode(Request $request, string $table, Property $property): array
    {
        $this->normalizeCode($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D',
                Rule::unique($table, 'code')->where('property_id', $property->id),
            ],
        ]);

        return ['name' => $data['name'], 'code' => $data['code']];
    }

    private function normalizeCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
    }
}
