<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', '+1 month');
        $endDate = clone $startDate;
        $endDate->modify('+12 months');
        
        return [
            'unit_id' => Unit::factory(),
            'tenant_id' => Tenant::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => $this->faker->numberBetween(800, 3000),
            'security_deposit' => $this->faker->numberBetween(500, 2000),
            'lease_type' => $this->faker->randomElement(['fixed', 'month-to-month']),
            'payment_day' => $this->faker->numberBetween(1, 5),
            'status' => $this->faker->randomElement(['active', 'pending', 'expired', 'terminated']),
            'document_path' => null,
            'notes' => $this->faker->optional(0.3)->sentence()
        ];
    }
}
