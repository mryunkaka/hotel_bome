<?php

namespace App\Http\Controllers\Properties;

use App\Models\Guest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GuestController
{
    public function index(Request $request, Property $property): View
    {
        abort_if(! $property->is_active, 404);

        $query = DB::table('guests')
            ->where('property_id', $property->id)
            ->select('id', 'display_name', 'notes', 'is_active', 'created_at', 'updated_at');

        if ($search = $request->string('q')->trim()->value()) {
            abort_if(mb_strlen($search) > 100, 422);
            $query->where('display_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%');
        }

        $filter = $request->string('status')->value();
        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filter !== '') {
            abort(422);
        }

        return view('properties.guests.index', [
            'property' => $property,
            'guests' => $query->orderBy('display_name')->simplePaginate(20)->withQueryString(),
        ]);
    }

    public function create(Property $property): View
    {
        abort_if(! $property->is_active, 404);

        return view('properties.guests.form', [
            'property' => $property,
            'guest' => new Guest,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        abort_if(! $property->is_active, 404);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($property, $data, $request): void {
            $guest = new Guest($data);
            $guest->property_id = $property->id;
            $guest->save();

            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'event_type' => 'created',
                'changes' => json_encode(['display_name' => $data['display_name']]),
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('properties.guests.index', $property)
            ->with('status', __('Guest created.'));
    }

    public function edit(Property $property, Guest $guest): View
    {
        abort_if((int) $guest->property_id !== (int) $property->id, 404);

        return view('properties.guests.form', compact('property', 'guest'));
    }

    public function update(Request $request, Property $property, Guest $guest): RedirectResponse
    {
        abort_if((int) $guest->property_id !== (int) $property->id, 404);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($guest, $property, $data, $request): void {
            $changes = [];
            if ($guest->display_name !== $data['display_name']) {
                $changes['display_name'] = ['from' => $guest->display_name, 'to' => $data['display_name']];
            }
            if (($guest->notes ?? '') !== ($data['notes'] ?? '')) {
                $changes['notes'] = ['from' => $guest->notes, 'to' => $data['notes'] ?? null];
            }

            $guest->update($data);

            if ($changes !== []) {
                DB::table('guest_events')->insert([
                    'property_id' => $property->id,
                    'guest_id' => $guest->id,
                    'event_type' => 'updated',
                    'changes' => json_encode($changes),
                    'actor_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('properties.guests.index', $property)
            ->with('status', __('Guest updated.'));
    }

    public function deactivate(Request $request, Property $property, Guest $guest): RedirectResponse
    {
        abort_if((int) $guest->property_id !== (int) $property->id, 404);

        DB::transaction(function () use ($guest, $property, $request): void {
            $locked = Guest::query()
                ->whereKey($guest->id)
                ->where('property_id', $property->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            abort_if($locked === null, 404);

            $locked->forceFill(['is_active' => false])->save();

            DB::table('guest_events')->insert([
                'property_id' => $property->id,
                'guest_id' => $guest->id,
                'event_type' => 'deactivated',
                'changes' => null,
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('properties.guests.index', $property)
            ->with('status', __('Guest deactivated.'));
    }
}
