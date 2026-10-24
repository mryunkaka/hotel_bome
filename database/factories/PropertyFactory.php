<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Hotel',
            'code' => Str::upper(fake()->unique()->bothify('HTL-####')),
            'is_active' => true,
        ];
    }
}
