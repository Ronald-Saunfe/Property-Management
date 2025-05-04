<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        // Create properties linked to users with role 'landlord'
        User::where('role', 'landlord')->each(function ($user) {
            Property::factory(rand(1, 3))->create([
                'user_id' => $user->id
            ]);
        });
        
        // Create additional properties if needed
        if (Property::count() < 10) {
            Property::factory(10 - Property::count())->create();
        }
    }
}