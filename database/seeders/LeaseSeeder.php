<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class LeaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create leases for some units (not all, to have some vacant units)
        Unit::inRandomOrder()->take(Unit::count() * 0.7)->get()->each(function ($unit) {
            Lease::factory()->create([
                'unit_id' => $unit->id,
                'monthly_rent' => $unit->monthly_rent,
                'security_deposit' => $unit->monthly_rent * 0.8
            ]);
        });
    }
}