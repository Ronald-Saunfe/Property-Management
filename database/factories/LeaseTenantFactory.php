<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\LeaseTenant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaseTenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'tenant_id' => Tenant::factory(),
            'is_primary' => $this->faker->boolean(70),
        ];
    }
}