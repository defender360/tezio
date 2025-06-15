<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Get current authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app('current_tenant');
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->hasRole('admin') ? 'admin' : ($user->hasRole('agent') ? 'agent' : 'user'),
                'tenant_id' => $user->tenant_id,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'settings' => $tenant->config ?? [],
            ]
        ]);
    }
}