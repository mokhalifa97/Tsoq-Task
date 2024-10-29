<?php
namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantDatabaseSwitcher
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');
        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID is required in the X-Tenant-ID header.'], 400);
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            if (App::environment('testing')) {
                Config::set('database.connections.tenant.database', ':memory:');
                DB::setDefaultConnection('testing'); 
            }else{
                Config::set('database.connections.tenant.database', $tenant->database_name);
                DB::setDefaultConnection('tenant');
            }
        } else {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }
        return $next($request);
    }

}
