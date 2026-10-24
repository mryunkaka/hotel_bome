<?php

namespace App\Http\Controllers\Properties;

use App\Models\Property;
use App\Services\Properties\EngineeringAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EngineeringAssetController
{
    public function index(Request $request, Property $property): View
    {
        abort_unless($property->is_active, 404);
        $status = $request->validate(['status' => ['sometimes', 'in:active,inactive']])['status'] ?? 'active';
        $assets = DB::table('engineering_assets')->where('property_id', $property->id)
            ->where('is_active', $status !== 'inactive')
            ->orderBy('is_active', 'desc')->orderBy('name')->paginate(20)->withQueryString();
        $events = DB::table('engineering_asset_events as events')
            ->join('engineering_assets as assets', function ($join): void {
                $join->on('assets.id', '=', 'events.asset_id')->on('assets.property_id', '=', 'events.property_id');
            })
            ->join('users', 'users.id', '=', 'events.actor_id')
            ->where('events.property_id', $property->id)
            ->orderByDesc('events.id')->limit(50)
            ->get(['events.event_type', 'events.reason', 'events.created_at', 'assets.code as asset_code', 'users.name as actor_name']);
        $editAsset = null;
        if ($request->integer('edit') > 0) {
            $editAsset = app(EngineeringAssets::class)->asset($property->id, $request->integer('edit'));
        }

        return view('properties.engineering.assets', compact('property', 'assets', 'editAsset', 'events'));
    }

    public function store(Request $request, Property $property, EngineeringAssets $service): RedirectResponse
    {
        $data = $this->validated($request, $property->id);
        $service->create($property->id, $data, $request->user());

        return back()->with('status', 'Asset created.');
    }

    public function update(Request $request, Property $property, int $asset, EngineeringAssets $service): RedirectResponse
    {
        $data = $this->validated($request, $property->id, $asset);
        $service->update($property->id, $asset, $data, $request->user());

        return redirect()->route('properties.operations.assets.index', $property)->with('status', 'Asset updated.');
    }

    public function deactivate(Request $request, Property $property, int $asset, EngineeringAssets $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $service->deactivate($property->id, $asset, $data['reason'], $request->user());

        return back()->with('status', 'Asset deactivated.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $propertyId = null, ?int $assetId = null): array
    {
        $code = $request->input('code');
        if (is_string($code)) {
            $request->merge(['code' => strtoupper($code)]);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/', 'unique:engineering_assets,code'.($propertyId && $assetId ? ','.$assetId.',id,property_id,'.$propertyId : ',NULL,id,property_id,'.($propertyId ?? 0))],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:160'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:operational,under_repair,retired'],
        ]);
    }
}
