<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\LeaseTenant;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LeaseTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Assign tenants to leases
        Lease::all()->each(function ($lease) {
            // Each lease gets 1-3 tenants
            $tenantCount = rand(1, 3);
            $tenants = Tenant::inRandomOrder()->take($tenantCount)->get();
            
            foreach ($tenants as $index => $tenant) {
                LeaseTenant::create([
                    'lease_id' => $lease->id,
                    'tenant_id' => $tenant->id,
                    'is_primary' => $index === 0 // First tenant is primary
                ]);
            }
        });
    }
}