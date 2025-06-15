<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get executive dashboard data
     */
    public function executive(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'nullable|boolean'
        ]);

        $filters = $request->only(['start_date', 'end_date']);
        
        if ($request->get('refresh')) {
            cache()->forget('executive_dashboard_' . md5(json_encode($filters)));
        }

        $data = $this->dashboardService->getExecutiveDashboard($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
            'generated_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Get operational dashboard data
     */
    public function operational(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'nullable|boolean'
        ]);

        $filters = $request->only(['start_date', 'end_date']);
        
        if ($request->get('refresh')) {
            cache()->forget('operational_dashboard_' . md5(json_encode($filters)));
        }

        $data = $this->dashboardService->getOperationalDashboard($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
            'generated_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Get team dashboard data
     */
    public function team(Request $request): JsonResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'refresh' => 'nullable|boolean'
        ]);

        $teamId = $request->get('team_id');
        $filters = $request->only(['start_date', 'end_date']);
        
        if ($request->get('refresh')) {
            cache()->forget("team_dashboard_{$teamId}_" . md5(json_encode($filters)));
        }

        $data = $this->dashboardService->getTeamDashboard($teamId, $filters);

        return response()->json([
            'success' => true,
            'data' => $data,
            'generated_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Get basic metrics (legacy endpoint for backward compatibility)
     */
    public function metrics(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => now()->subMonth(),
            'end_date' => now()
        ];

        $data = $this->dashboardService->getExecutiveDashboard($filters);

        return response()->json([
            'total_incidents' => $data['kpis']['total_tickets']['current'] ?? 0,
            'open_incidents' => 32, // This would come from real-time query
            'overdue_incidents' => 5, // This would come from real-time query
            'sla_compliance' => $data['kpis']['sla_compliance']['current'] ?? 0,
            'incidents_today' => 8, // This would come from real-time query
            'avg_resolution_time' => $data['kpis']['avg_resolution_time']['current'] ?? 0
        ]);
    }

    /**
     * Get recent incidents (legacy endpoint for backward compatibility)
     */
    public function recentIncidents(Request $request): JsonResponse
    {
        // This would be replaced with real data from IncidentAnalytics service
        return response()->json([
            [
                'id' => '1',
                'number' => 'INC-2024-001',
                'title' => 'Email server connectivity issue',
                'priority' => 'high',
                'status' => 'in_progress',
                'assigned_user' => [
                    'id' => '1',
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'created_at' => '2024-01-14T10:30:00Z',
                'sla_response_target' => '2024-01-14T12:30:00Z'
            ],
            [
                'id' => '2',
                'number' => 'INC-2024-002',
                'title' => 'VPN access not working for remote users',
                'priority' => 'critical',
                'status' => 'assigned',
                'assigned_user' => [
                    'id' => '2',
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com'
                ],
                'created_at' => '2024-01-14T09:15:00Z',
                'sla_response_target' => '2024-01-14T10:15:00Z',
                'is_overdue' => true
            ],
            [
                'id' => '3',
                'number' => 'INC-2024-003',
                'title' => 'Printer not responding on 3rd floor',
                'priority' => 'low',
                'status' => 'new',
                'assigned_user' => null,
                'created_at' => '2024-01-14T11:45:00Z',
                'sla_response_target' => '2024-01-15T11:45:00Z'
            ]
        ]);
    }
}