<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Create units linked to existing properties
        Property::all()->each(function ($property) {
            Unit::factory(rand(3, 10))->create([
                'property_id' => $property->id
            ]);
        });
    }
}