<?php

namespace App\Http\Controllers\Properties;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use UnexpectedValueException;

class PropertyController
{
    public function index(): View
    {
        return view('properties.index', [
            'properties' => Property::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('properties.form', ['property' => new Property]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        Property::create($data);

        return redirect()->route('properties.index')->with('status', __('Property created.'));
    }

    public function edit(Property $property): View
    {
        return view('properties.form', compact('property'));
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $property->update($this->validatedData($request, $property));

        return redirect()->route('properties.index')->with('status', __('Property updated.'));
    }

    public function deactivate(Property $property): RedirectResponse
    {
        DB::transaction(function () use ($property): void {
            $lockedProperty = Property::query()
                ->whereKey($property->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            abort_if($lockedProperty === null, 404);
            $lockedProperty->forceFill(['is_active' => false])->save();
        });

        return redirect()->route('properties.index')->with('status', __('Property deactivated.'));
    }

    /** @return array{name: string, code: string} */
    private function validatedData(Request $request, ?Property $property = null): array
    {
        $input = $request->only(['name', 'code']);

        if (is_string($input['code'] ?? null)) {
            $input['code'] = strtoupper(trim($input['code']));
        }

        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D',
                Rule::unique('properties', 'code')->ignore($property),
            ],
        ])->validate();

        $name = $validated['name'] ?? null;
        $code = $validated['code'] ?? null;

        if (! is_string($name) || ! is_string($code)) {
            throw new UnexpectedValueException('Validated property fields must be strings.');
        }

        return ['name' => $name, 'code' => $code];
    }
}
