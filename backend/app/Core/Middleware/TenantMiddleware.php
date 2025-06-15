<?php

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Core\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution for central domain
        if ($this->tenantService->isCentralDomain($request->getHost())) {
            return $next($request);
        }

        // Try to identify tenant by domain
        $tenant = $this->tenantService->findByDomain($request->getHost());
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        if (!$tenant->hasValidSubscription()) {
            abort(403, 'Invalid subscription');
        }

        // Set the current tenant
        $this->tenantService->set($tenant);
        
        // Add tenant to request for easy access
        $request->merge(['tenant' => $tenant]);
        
        return $next($request);
    }
}