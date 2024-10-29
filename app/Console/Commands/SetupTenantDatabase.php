<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SetupTenantDatabase extends Command
{
    protected $signature = 'tenant:setup {name} {database}';
    protected $description = 'Set up a new tenant database and entry in the tenants table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');
        $databaseName = $this->argument('database');
        $tenant = Tenant::create([
            'name' => $name,
            'database_name' => $databaseName,
        ]);

        DB::statement("CREATE DATABASE IF NOT EXISTS `$databaseName`");
        Config::set('database.connections.tenant.database', $databaseName);
        DB::setDefaultConnection('tenant');

        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenants',
            '--database' => 'tenant'
        ]);
        $this->info("Tenant $name with database $databaseName has been set up successfully.");
    }
}
