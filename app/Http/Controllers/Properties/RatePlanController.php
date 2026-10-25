<?php

namespace App\Http\Controllers\Properties;

use App\Models\Property;
use App\Models\RatePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RatePlanController
{
    public function index(Request $request, Property $property): View
    {
        abort_if(! $property->is_active, 404);

        $query = DB::table('rate_plans')
            ->where('property_id', $property->id)
            ->select('id', 'name', 'code', 'base_price_per_night', 'tax_percentage', 'effective_from', 'effective_until', 'is_active', 'created_at');

        if ($search = $request->string('q')->trim()->value()) {
            abort_if(mb_strlen($search) > 100, 422);
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($q) use ($escaped): void {
                $q->where('name', 'like', '%'.$escaped.'%')
                    ->orWhere('code', 'like', '%'.$escaped.'%');
            });
        }

        $filter = $request->string('status')->value();
        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filter !== '') {
            abort(422);
        }

        return view('properties.rate-plans.index', [
            'property' => $property,
            'ratePlans' => $query->orderBy('name')->simplePaginate(20)->withQueryString(),
        ]);
    }

    public function create(Property $property): View
    {
        abort_if(! $property->is_active, 404);

        return view('properties.rate-plans.form', [
            'property' => $property,
            'ratePlan' => new RatePlan,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        abort_if(! $property->is_active, 404);

        $data = $this->validatedData($request, $property);

        DB::transaction(function () use ($property, $data, $request): void {
            $ratePlan = new RatePlan($data);
            $ratePlan->property_id = $property->id;
            $ratePlan->save();

            DB::table('rate_plan_events')->insert([
                'property_id' => $property->id,
                'rate_plan_id' => $ratePlan->id,
                'event_type' => 'created',
                'changes' => json_encode(['name' => $data['name'], 'code' => $data['code'], 'base_price_per_night' => $data['base_price_per_night']]),
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('properties.rate-plans.index', $property)
            ->with('status', __('Rate plan created.'));
    }

    public function edit(Property $property, RatePlan $ratePlan): View
    {
        abort_if((int) $ratePlan->property_id !== (int) $property->id, 404);

        return view('properties.rate-plans.form', compact('property', 'ratePlan'));
    }

    public function update(Request $request, Property $property, RatePlan $ratePlan): RedirectResponse
    {
        abort_if((int) $ratePlan->property_id !== (int) $property->id, 404);

        $data = $this->validatedData($request, $property, $ratePlan);

        DB::transaction(function () use ($ratePlan, $property, $data, $request): void {
            $changes = [];
            foreach (['name', 'code', 'base_price_per_night', 'tax_percentage', 'effective_from', 'effective_until'] as $field) {
                $old = $ratePlan->getRawOriginal($field);
                $new = $data[$field] ?? null;
                if ((string) $old !== (string) ($new ?? '')) {
                    $changes[$field] = ['from' => $old, 'to' => $new];
                }
            }

            $ratePlan->update($data);

            if ($changes !== []) {
                DB::table('rate_plan_events')->insert([
                    'property_id' => $property->id,
                    'rate_plan_id' => $ratePlan->id,
                    'event_type' => 'updated',
                    'changes' => json_encode($changes),
                    'actor_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('properties.rate-plans.index', $property)
            ->with('status', __('Rate plan updated.'));
    }

    public function deactivate(Request $request, Property $property, RatePlan $ratePlan): RedirectResponse
    {
        abort_if((int) $ratePlan->property_id !== (int) $property->id, 404);

        DB::transaction(function () use ($ratePlan, $property, $request): void {
            $locked = RatePlan::query()
                ->whereKey($ratePlan->id)
                ->where('property_id', $property->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            abort_if($locked === null, 404);

            $locked->forceFill(['is_active' => false])->save();

            DB::table('rate_plan_events')->insert([
                'property_id' => $property->id,
                'rate_plan_id' => $ratePlan->id,
                'event_type' => 'deactivated',
                'changes' => null,
                'actor_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('properties.rate-plans.index', $property)
            ->with('status', __('Rate plan deactivated.'));
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, Property $property, ?RatePlan $ratePlan = null): array
    {
        $input = $request->only(['name', 'code', 'base_price_per_night', 'tax_percentage', 'effective_from', 'effective_until']);

        if (is_string($input['code'] ?? null)) {
            $input['code'] = strtoupper(trim($input['code']));
        }

        $validated = validator($input, [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D',
                Rule::unique('rate_plans', 'code')
                    ->where('property_id', $property->id)
                    ->ignore($ratePlan),
            ],
            'base_price_per_night' => ['required', 'integer', 'min:0', 'max:999999999'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ])->validate();

        return $validated;
    }
}
