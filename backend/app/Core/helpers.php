<?php

use App\Core\Services\TenantService;
use App\Domains\Tenant\Models\Tenant;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant instance.
     */
    function tenant(): ?Tenant
    {
        return app(TenantService::class)->get();
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Get the current tenant ID.
     */
    function tenant_id(): ?string
    {
        return tenant()?->id;
    }
}

if (!function_exists('is_central_domain')) {
    /**
     * Check if the current request is for the central domain.
     */
    function is_central_domain(): bool
    {
        return app(TenantService::class)->isCentralDomain(request()->getHost());
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format bytes to human readable format.
     */
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('generate_ticket_number')) {
    /**
     * Generate a unique ticket number.
     */
    function generate_ticket_number(string $prefix = 'INC'): string
    {
        $year = date('Y');
        $tenant_prefix = tenant() ? substr(tenant()->slug, 0, 3) : 'SYS';
        
        return strtoupper($prefix . '-' . $tenant_prefix . '-' . $year . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT));
    }
}