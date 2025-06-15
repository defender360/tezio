<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use App\Models\User;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class Auth0Middleware
{
    public function handle(Request $request, Closure $next)
    {
        // Dev mode bypass - allow without token in local environment
        if (env('APP_ENV') === 'local' && !$request->bearerToken()) {
            // Set default tenant and user for development
            $tenant = Tenant::where('subdomain', 'dev')->first();
            if (!$tenant) {
                $tenant = Tenant::create([
                    'id' => '550e8400-e29b-41d4-a716-446655440000', // Valid UUID
                    'name' => 'Development Tenant',
                    'subdomain' => 'dev',
                    'slug' => 'dev',
                    'is_active' => true
                ]);
            }
            
            app()->instance('current_tenant', $tenant);
            // For dev mode, we'll skip the row level security
            // \DB::statement("SET SESSION app.current_tenant_id = ?", [$tenant->id]);
            
            $user = User::where('email', 'dev@defender360.com')->first();
            if (!$user) {
                $user = User::create([
                    'id' => '550e8400-e29b-41d4-a716-446655440001', // Valid UUID
                    'email' => 'dev@defender360.com',
                    'name' => 'Dev User',
                    'auth0_id' => 'dev-user-1',
                    'tenant_id' => $tenant->id
                ]);
            }
            
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
            return $next($request);
        }
        
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }
        
        try {
            // Decode and verify JWT
            $decoded = $this->verifyToken($token);
            
            // Extract tenant from token
            $tenantId = $decoded->{config('auth0.custom_claims.tenant_id')} ?? null;
            
            if (!$tenantId) {
                return response()->json(['error' => 'No tenant specified'], 403);
            }
            
            // Set current tenant
            $tenant = Tenant::findOrFail($tenantId);
            app()->instance('current_tenant', $tenant);
            
            // Set tenant in database session
            // \DB::statement("SET SESSION app.current_tenant_id = ?", [$tenantId]);
            
            // Find or create user
            $user = User::firstOrCreate(
                ['auth0_id' => $decoded->sub],
                [
                    'email' => $decoded->email,
                    'name' => $decoded->name ?? $decoded->email,
                    'tenant_id' => $tenantId,
                ]
            );
            
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
        
        return $next($request);
    }
    
    private function verifyToken($token)
    {
        $jwks = Cache::remember('auth0_jwks', 3600, function () {
            $response = \Http::get('https://' . config('auth0.domain') . '/.well-known/jwks.json');
            return $response->json();
        });
        
        $keys = JWK::parseKeySet($jwks);
        
        return JWT::decode($token, $keys);
    }
}