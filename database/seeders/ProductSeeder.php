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
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::setDefaultConnection('tenant');
            Product::factory()->count(10)->create(); 
            $this->command->info("Seeded products for tenant ID {$tenant->id} with database {$tenant->database_name}");
        }
        DB::setDefaultConnection(config('database.default'));
    }
}
