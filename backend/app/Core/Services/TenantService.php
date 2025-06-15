<?php

namespace App\Core\Services;

use App\Domains\Tenant\Models\Tenant;
use App\Core\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantService
{
    /**
     * The current tenant instance.
     */
    protected ?Tenant $tenant = null;

    /**
     * Get the current tenant.
     */
    public function get(): ?Tenant
    {
        if ($this->tenant) {
            return $this->tenant;
        }

        // Try to get from authenticated user
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user instanceof User) {
                $this->tenant = $user->currentTenant();
            }
        }

        return $this->tenant;
    }

    /**
     * Set the current tenant.
     */
    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        
        if ($tenant) {
            Session::put('current_tenant_id', $tenant->id);
            
            // Set tenant-specific configurations
            config([
                'app.name' => $tenant->company_name,
                'app.timezone' => $tenant->timezone,
                'app.locale' => $tenant->locale,
                'mail.from.name' => $tenant->company_name,
            ]);
            
            // Set timezone for the request
            date_default_timezone_set($tenant->timezone);
        } else {
            Session::forget('current_tenant_id');
        }
    }

    /**
     * Switch to a different tenant.
     */
    public function switch(string $tenantId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        if (!$user instanceof User) {
            return false;
        }

        // Check if user has access to the tenant
        if (!$user->belongsToTenant($tenantId)) {
            return false;
        }

        $tenant = Tenant::find($tenantId);
        
        if (!$tenant || !$tenant->isActive()) {
            return false;
        }

        $this->set($tenant);
        
        // Update last activity
        $tenant->touchLastActivity();
        
        return true;
    }

    /**
     * Clear the current tenant.
     */
    public function clear(): void
    {
        $this->set(null);
    }

    /**
     * Find tenant by domain.
     */
    public function findByDomain(string $domain): ?Tenant
    {
        // First, try to find by custom domain
        $tenantDomain = \App\Domains\Tenant\Models\TenantDomain::where('domain', $domain)
            ->where('is_verified', true)
            ->first();
            
        if ($tenantDomain) {
            return $tenantDomain->tenant;
        }

        // Then, try to find by subdomain
        $subdomain = $this->extractSubdomain($domain);
        
        if ($subdomain) {
            return Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();
        }

        return null;
    }

    /**
     * Extract subdomain from domain.
     */
    protected function extractSubdomain(string $domain): ?string
    {
        $centralDomain = config('app.central_domain');
        
        if (!$centralDomain) {
            return null;
        }

        // Remove protocol if present
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        
        // Remove port if present
        $domain = preg_replace('/:\d+$/', '', $domain);
        
        // Check if it's a subdomain
        if (str_ends_with($domain, '.' . $centralDomain)) {
            $subdomain = str_replace('.' . $centralDomain, '', $domain);
            
            // Ignore www
            if ($subdomain === 'www') {
                return null;
            }
            
            return $subdomain;
        }

        return null;
    }

    /**
     * Check if the current request is for the central domain.
     */
    public function isCentralDomain(string $domain): bool
    {
        $centralDomain = config('app.central_domain');
        
        if (!$centralDomain) {
            return false;
        }

        // Remove protocol and port
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/:\d+$/', '', $domain);
        
        return $domain === $centralDomain || $domain === 'www.' . $centralDomain;
    }

    /**
     * Get tenant by ID with validation.
     */
    public function find(string $id): ?Tenant
    {
        $tenant = Tenant::find($id);
        
        if (!$tenant || !$tenant->hasValidSubscription()) {
            return null;
        }

        return $tenant;
    }

    /**
     * Check if multi-tenancy is enabled.
     */
    public function isEnabled(): bool
    {
        return config('tenancy.enabled', true);
    }
}