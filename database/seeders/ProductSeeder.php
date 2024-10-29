<?php

namespace Database\Seeders;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Retrieve all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Set the database connection to the tenant's database
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::setDefaultConnection('tenant');

            // Seed products for this tenant
            Product::factory()->count(10)->create(); // Adjust the count as needed

            // Optional: Log output to show seeding progress
            $this->command->info("Seeded products for tenant ID {$tenant->id} with database {$tenant->database_name}");
        }

        // Reset the database connection back to the default
        DB::setDefaultConnection(config('database.default'));
    }
}
