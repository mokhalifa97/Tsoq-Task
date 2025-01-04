<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use PDOException;

class SetupTenant extends Command
{
    protected $signature = 'tenant:setup {name} {database}';

    protected $description = 'Sets up a new tenant with a separate database and runs migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $database = $this->argument('database');

        // 1. Create tenant record
        try {
            $tenant = Tenant::create([
                'name' => $name,
                'database_name' => $database,
            ]);
            $this->info("Tenant '{$name}' created with database '{$database}'.");
        } catch (\Exception $e) {
            $this->error("Error creating tenant: " . $e->getMessage());
            return 1;
        }

        // 2. Create the database
        try {
            DB::statement("CREATE DATABASE `{$database}`");
            $this->info("Database '{$database}' created successfully.");
        } catch (PDOException $e) {
            $this->error("Error creating database: " . $e->getMessage());
            // Optionally, delete the tenant record if DB creation fails
            $tenant->delete();
            return 1;
        }

        // 3. Run tenant migrations
        try {
            // Set up a temporary tenant connection
            Config::set('database.connections.tenant_temp', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $database,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Run migrations on the tenant's database
            Artisan::call('migrate', [
                '--database' => 'tenant_temp',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            $this->info("Migrations run successfully for tenant '{$name}'.");
        } catch (\Exception $e) {
            $this->error("Error running migrations: " . $e->getMessage());
            // Optionally, rollback database and tenant record
            DB::statement("DROP DATABASE `{$database}`");
            $tenant->delete();
            return 1;
        }

        // Clean up the temporary connection
        Config::set('database.connections.tenant_temp', null);

        return 0;
    }
}
