<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'unit_number' => $this->faker->bothify('##??'),
            'floor_plan' => $this->faker->randomElement(['Studio', '1BR', '2BR', '3BR', null]),
            'square_feet' => $this->faker->numberBetween(500, 2500),
            'bedrooms' => $this->faker->numberBetween(0, 4),
            'bathrooms' => $this->faker->randomElement([1, 1.5, 2, 2.5, 3]),
            'monthly_rent' => $this->faker->numberBetween(800, 3000),
            'status' => $this->faker->randomElement(['vacant', 'occupied', 'maintenance']),
            'features' => json_encode($this->faker->randomElements(['dishwasher', 'washer/dryer', 'balcony', 'fireplace', 'hardwood floors', 'central air'], $this->faker->numberBetween(1, 4))),
        ];
    }
}