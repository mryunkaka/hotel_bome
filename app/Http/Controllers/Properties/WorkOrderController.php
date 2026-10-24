<?php

namespace App\Http\Controllers\Properties;

use App\Models\Property;
use App\Services\Properties\WorkOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkOrderController
{
    public function properties(Request $request): View
    {
        $properties = $request->user()->is_platform_admin
            ? DB::table('properties')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
            : DB::table('property_user')->join('properties', 'properties.id', '=', 'property_user.property_id')
                ->where('property_user.user_id', $request->user()->id)->where('property_user.role', 'operations')
                ->where('properties.is_active', true)->orderBy('properties.name')->get(['properties.id', 'properties.name', 'properties.code']);

        return view('properties.engineering.properties', compact('properties'));
    }

    public function index(Request $request, Property $property): View
    {
        abort_unless($property->is_active, 404);
        $status = $request->validate(['status' => ['sometimes', 'nullable', 'in:open,in_progress,resolved,cancelled']])['status'] ?? null;
        $orders = DB::table('work_orders as orders')
            ->leftJoin('rooms', function ($join): void {
                $join->on('rooms.id', '=', 'orders.room_id')->on('rooms.property_id', '=', 'orders.property_id');
            })
            ->leftJoin('users as assignees', 'assignees.id', '=', 'orders.assigned_to')
            ->where('orders.property_id', $property->id)
            ->leftJoin('engineering_assets as assets', function ($join): void {
                $join->on('assets.id', '=', 'orders.asset_id')->on('assets.property_id', '=', 'orders.property_id');
            })
            ->select(['orders.id', 'orders.property_id', 'orders.room_id', 'orders.asset_id', 'orders.assigned_to', 'orders.status', 'orders.title', 'orders.description', 'orders.created_at', 'rooms.code as room_code', 'assets.code as asset_code', 'assets.name as asset_name', 'assignees.name as assignee_name'])
            ->when($status !== null, fn ($query) => $query->where('orders.status', $status))
            ->orderByDesc('orders.created_at')->paginate(20)->withQueryString();
        $rooms = DB::table('rooms')->where('property_id', $property->id)->where('is_active', true)->orderBy('code')->get(['id', 'code']);
        $assets = DB::table('engineering_assets')->where('property_id', $property->id)->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $staff = DB::table('property_user')->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $property->id)->where('property_user.role', 'operations')->where('users.status', 'active')
            ->orderBy('users.name')->get(['users.id', 'users.name']);
        $events = DB::table('work_order_events as events')->join('users', 'users.id', '=', 'events.actor_id')
            ->where('events.property_id', $property->id)->whereIn('events.work_order_id', $orders->getCollection()->pluck('id'))
            ->orderBy('events.id')->get(['events.*', 'users.name as actor_name'])->groupBy('work_order_id');

        return view('properties.engineering.work-orders', compact('property', 'orders', 'rooms', 'assets', 'staff', 'events'));
    }

    public function show(Request $request, Property $property, int $workOrder, WorkOrders $service): View
    {
        abort_unless($property->is_active, 404);
        $order = $service->order($property->id, $workOrder);
        $room = $order->room_id === null ? null : DB::table('rooms')->where('property_id', $property->id)->where('id', $order->room_id)->value('code');
        $asset = $order->asset_id === null ? null : DB::table('engineering_assets')->where('property_id', $property->id)->where('id', $order->asset_id)->first(['code', 'name']);
        $assignee = $order->assigned_to === null ? null : DB::table('users')->where('id', $order->assigned_to)->value('name');
        $creator = DB::table('users')->where('id', $order->created_by)->value('name');
        $events = DB::table('work_order_events as events')->join('users', 'users.id', '=', 'events.actor_id')
            ->where('events.property_id', $property->id)->where('events.work_order_id', $workOrder)
            ->orderBy('events.id')->get(['events.*', 'users.name as actor_name']);

        return view('properties.engineering.work-order-show', compact('property', 'order', 'room', 'asset', 'assignee', 'creator', 'events'));
    }

    public function store(Request $request, Property $property, WorkOrders $service): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'integer'], 'asset_id' => ['nullable', 'integer'], 'assigned_to' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->create($property->id, isset($data['room_id']) ? (int) $data['room_id'] : null, isset($data['assigned_to']) ? (int) $data['assigned_to'] : null, $data['title'], $data['description'] ?? null, $request->user(), isset($data['asset_id']) ? (int) $data['asset_id'] : null);

        return back()->with('status', 'Work order created.');
    }

    public function assign(Request $request, Property $property, int $workOrder, WorkOrders $service): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'integer']]);
        $service->assign($property->id, $workOrder, isset($data['assigned_to']) ? (int) $data['assigned_to'] : null, $request->user());

        return back()->with('status', 'Assignment updated.');
    }

    public function transition(Request $request, Property $property, int $workOrder, string $action, WorkOrders $service): RedirectResponse
    {
        abort_unless(in_array($action, ['start', 'resolve', 'cancel'], true), 404);
        $data = $request->validate(['reason' => [$action === 'cancel' ? 'required' : 'nullable', 'string', 'max:500']]);
        $to = match ($action) {
            'start' => 'in_progress', 'resolve' => 'resolved', 'cancel' => 'cancelled'
        };
        $service->transition($property->id, $workOrder, $to, $data['reason'] ?? null, $request->user());

        return back()->with('status', 'Work order updated.');
    }
}
