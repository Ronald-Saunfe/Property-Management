<?php

namespace Database\Seeders;

use App\Models\PropertyManager;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertyManagerSeeder extends Seeder
{
    public function run(): void
    {
        // Create property managers linked to existing users with role 'agent'
        User::where('role', 'agent')->each(function ($user) {
            PropertyManager::factory()->create([
                'user_id' => $user->id
            ]);
        });
        
        // Create additional property managers if needed
        if (PropertyManager::count() < 5) {
            PropertyManager::factory(5 - PropertyManager::count())->create();
        }
    }
}