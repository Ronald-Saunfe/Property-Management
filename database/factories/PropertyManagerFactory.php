<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyManagerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'user_id' => User::factory(),
            'is_primary' => $this->faker->boolean(30),
            'company_name' => $this->faker->company(),
            'license_number' => $this->faker->numerify('LIC-######'),
            'years_of_experience' => $this->faker->numberBetween(1, 20),
            'bio' => $this->faker->paragraph(),
        ];
    }
}