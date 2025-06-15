<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Basic health check
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'Tezio Defender360 ITSM API',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Detailed status check
     */
    public function status(): JsonResponse
    {
        $checks = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // Redis check
        try {
            Redis::ping();
            $checks['redis'] = [
                'status' => 'healthy',
                'message' => 'Redis connection successful'
            ];
        } catch (\Exception $e) {
            $checks['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ];
        }

        // Cache check
        try {
            Cache::put('health_check', true, 10);
            Cache::get('health_check');
            $checks['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache is working'
            ];
        } catch (\Exception $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache failed: ' . $e->getMessage()
            ];
        }

        // Determine overall status
        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            }
        }

        return response()->json([
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String()
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * System metrics
     */
    public function metrics(): JsonResponse
    {
        $metrics = [
            'memory' => [
                'usage' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'uptime' => [
                'started_at' => app()->startedAt ?? now()->toIso8601String(),
                'uptime_seconds' => time() - (app()->startedAt ? app()->startedAt->timestamp : time())
            ],
            'php' => [
                'version' => PHP_VERSION,
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'laravel' => [
                'version' => app()->version()
            ]
        ];

        return response()->json($metrics);
    }
}