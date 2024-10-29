<?php
namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantDatabaseSwitcher
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');
        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID is required in the X-Tenant-ID header.']);
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::setDefaultConnection('tenant');
        } else {
            return response()->json(['error' => 'Tenant not found.']);
        }
        return $next($request);
    }

}
