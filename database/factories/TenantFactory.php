<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'date_of_birth' => $this->faker->dateTimeBetween('-70 years', '-18 years'),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'occupation' => $this->faker->jobTitle(),
            'income' => $this->faker->numberBetween(30000, 120000),
            'credit_score' => $this->faker->numberBetween(550, 800),
            'status' => $this->faker->randomElement(['active', 'pending', 'former']),
        ];
    }
}