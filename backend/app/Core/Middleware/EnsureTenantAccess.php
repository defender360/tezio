<?php

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $tenant = tenant();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // Check if user belongs to the current tenant
        if (!$user->belongsToTenant($tenant->id)) {
            abort(403, 'Access denied to this tenant');
        }

        // Check role if specified
        if ($role) {
            $userRole = $user->getRoleInTenant($tenant->id);
            
            if (!$this->hasRequiredRole($userRole, $role)) {
                abort(403, 'Insufficient permissions');
            }
        }

        return $next($request);
    }

    /**
     * Check if user has required role.
     */
    protected function hasRequiredRole(?string $userRole, string $requiredRole): bool
    {
        $roleHierarchy = [
            'viewer' => 1,
            'agent' => 2,
            'manager' => 3,
            'admin' => 4,
        ];

        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
}