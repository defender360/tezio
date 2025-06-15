<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Analytics\IncidentAnalytics;
use App\Services\Analytics\ChangeAnalytics;
use App\Services\Analytics\SlaAnalytics;
use App\Services\Analytics\WorkloadAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalyticsController extends Controller
{
    protected $incidentAnalytics;
    protected $changeAnalytics;
    protected $slaAnalytics;
    protected $workloadAnalytics;

    public function __construct(
        IncidentAnalytics $incidentAnalytics,
        ChangeAnalytics $changeAnalytics,
        SlaAnalytics $slaAnalytics,
        WorkloadAnalytics $workloadAnalytics
    ) {
        $this->incidentAnalytics = $incidentAnalytics;
        $this->changeAnalytics = $changeAnalytics;
        $this->slaAnalytics = $slaAnalytics;
        $this->workloadAnalytics = $workloadAnalytics;
    }

    /**
     * Get incident analytics
     */
    public function incidents(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:hour,day,week,month,quarter,year',
            'category' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,critical'
        ]);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $groupBy = $request->get('group_by', 'day');
        $filters = $request->only(['category', 'priority']);

        $data = [
            'trends' => $this->incidentAnalytics->getTrends($startDate, $endDate, $groupBy),
            'category_distribution' => $this->incidentAnalytics->getCategoryDistribution($startDate, $endDate),
            'priority_metrics' => $this->incidentAnalytics->getPriorityMetrics($startDate, $endDate),
            'mttr_analysis' => $this->incidentAnalytics->getMTTRMetrics($startDate, $endDate),
            'aging_analysis' => $this->incidentAnalytics->getAgingAnalysis(),
            'reopen_rate' => $this->incidentAnalytics->getReopenRate($startDate, $endDate),
            'pattern_analysis' => $this->incidentAnalytics->getPatternAnalysis($startDate, $endDate),
            'root_cause_analysis' => $this->incidentAnalytics->getRootCauseAnalysis($startDate, $endDate)
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy
            ]
        ]);
    }

    /**
     * Get change analytics
     */
    public function changes(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month,quarter,year',
            'type' => 'nullable|in:standard,normal,emergency',
            'risk_level' => 'nullable|in:low,medium,high'
        ]);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $groupBy = $request->get('group_by', 'week');
        $filters = $request->only(['type', 'risk_level']);

        $data = [
            'success_rate' => $this->changeAnalytics->getSuccessRate($startDate, $endDate),
            'trends' => $this->changeAnalytics->getTrends($startDate, $endDate, $groupBy),
            'type_distribution' => $this->changeAnalytics->getTypeDistribution($startDate, $endDate),
            'risk_metrics' => $this->changeAnalytics->getRiskMetrics($startDate, $endDate),
            'approval_metrics' => $this->changeAnalytics->getApprovalMetrics($startDate, $endDate),
            'window_utilization' => $this->changeAnalytics->getWindowUtilization($startDate, $endDate),
            'failure_analysis' => $this->changeAnalytics->getFailureAnalysis($startDate, $endDate),
            'velocity_metrics' => $this->changeAnalytics->getVelocityMetrics($startDate, $endDate)
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy
            ]
        ]);
    }

    /**
     * Get SLA analytics
     */
    public function sla(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'service_id' => 'nullable|exists:services,id',
            'priority' => 'nullable|in:low,medium,high,critical'
        ]);

        $filters = $request->all();
        $data = $this->slaAnalytics->getOverview($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => [
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date')
            ]
        ]);
    }

    /**
     * Get workload analytics
     */
    public function workload(Request $request): JsonResponse
    {
        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $filters = $request->all();
        $data = $this->workloadAnalytics->getDistribution($filters);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get predictive analytics
     */
    public function predictive(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:incident_volume,sla_breach,resource_demand',
            'days' => 'nullable|integer|min:1|max:90'
        ]);

        $type = $request->get('type');
        $days = $request->get('days', 30);

        $data = match($type) {
            'incident_volume' => $this->incidentAnalytics->getVolumeForecast($days),
            'sla_breach' => $this->slaAnalytics->getBreachRisk(['forecast_days' => $days]),
            'resource_demand' => $this->workloadAnalytics->getResourceConstraints(['forecast_days' => $days]),
            default => []
        };

        return response()->json([
            'success' => true,
            'type' => $type,
            'forecast_days' => $days,
            'data' => $data
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|in:incidents,changes,sla,workload,executive',
            'format' => 'required|in:excel,csv,pdf',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $type = $request->get('type');
        $format = $request->get('format');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get data based on type
        $data = $this->getExportData($type, $startDate, $endDate);

        // Generate filename
        $filename = "analytics_{$type}_" . date('Y-m-d_His');

        // Export based on format
        return match($format) {
            'excel' => $this->exportExcel($data, $filename),
            'csv' => $this->exportCsv($data, $filename),
            'pdf' => $this->exportPdf($data, $filename, $type),
            default => response()->json(['error' => 'Invalid format'], 400)
        };
    }

    /**
     * Schedule analytics report
     */
    public function scheduleReport(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:incidents,changes,sla,workload,executive,custom',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:excel,pdf',
            'filters' => 'nullable|array',
            'active' => 'nullable|boolean'
        ]);

        // This would typically save to a scheduled_reports table
        $report = [
            'id' => uniqid(),
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'frequency' => $request->get('frequency'),
            'recipients' => $request->get('recipients'),
            'format' => $request->get('format'),
            'filters' => $request->get('filters', []),
            'active' => $request->get('active', true),
            'created_by' => auth()->id(),
            'created_at' => now()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Report scheduled successfully',
            'data' => $report
        ], 201);
    }

    /**
     * Get real-time metrics
     */
    public function realtime(Request $request): JsonResponse
    {
        $request->validate([
            'metrics' => 'nullable|array',
            'metrics.*' => 'in:active_incidents,queue_length,sla_at_risk,online_users,response_times'
        ]);

        $requestedMetrics = $request->get('metrics', [
            'active_incidents', 
            'queue_length', 
            'sla_at_risk', 
            'online_users',
            'response_times'
        ]);

        $data = [];
        
        foreach ($requestedMetrics as $metric) {
            $data[$metric] = $this->getRealTimeMetric($metric);
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => $data
        ]);
    }

    /**
     * Get drill-down data for a specific metric
     */
    public function drilldown(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|string',
            'dimension' => 'required|string',
            'value' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $metric = $request->get('metric');
        $dimension = $request->get('dimension');
        $value = $request->get('value');
        $startDate = $request->get('start_date', now()->subMonth());
        $endDate = $request->get('end_date', now());

        // This would perform specific drill-down queries based on the metric and dimension
        $data = $this->getDrilldownData($metric, $dimension, $value, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'metric' => $metric,
            'dimension' => $dimension,
            'value' => $value,
            'data' => $data
        ]);
    }

    /**
     * Get export data based on type
     */
    protected function getExportData($type, $startDate, $endDate)
    {
        return match($type) {
            'incidents' => [
                'trends' => $this->incidentAnalytics->getTrends($startDate, $endDate),
                'categories' => $this->incidentAnalytics->getCategoryDistribution($startDate, $endDate),
                'priorities' => $this->incidentAnalytics->getPriorityMetrics($startDate, $endDate)
            ],
            'changes' => [
                'success_rate' => $this->changeAnalytics->getSuccessRate($startDate, $endDate),
                'trends' => $this->changeAnalytics->getTrends($startDate, $endDate),
                'risk_analysis' => $this->changeAnalytics->getRiskMetrics($startDate, $endDate)
            ],
            'sla' => $this->slaAnalytics->getOverview(['start_date' => $startDate, 'end_date' => $endDate]),
            'workload' => $this->workloadAnalytics->getDistribution(['start_date' => $startDate, 'end_date' => $endDate]),
            default => []
        };
    }

    /**
     * Export data as Excel
     */
    protected function exportExcel($data, $filename)
    {
        // This would use Laravel Excel package to generate the file
        // For now, returning a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'Excel export would be generated here',
            'filename' => $filename . '.xlsx'
        ]);
    }

    /**
     * Export data as CSV
     */
    protected function exportCsv($data, $filename)
    {
        // This would generate a CSV file
        // For now, returning a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'CSV export would be generated here',
            'filename' => $filename . '.csv'
        ]);
    }

    /**
     * Export data as PDF
     */
    protected function exportPdf($data, $filename, $type)
    {
        // This would use Laravel DomPDF to generate the file
        // For now, returning a placeholder response
        return response()->json([
            'success' => true,
            'message' => 'PDF export would be generated here',
            'filename' => $filename . '.pdf'
        ]);
    }

    /**
     * Get real-time metric value
     */
    protected function getRealTimeMetric($metric)
    {
        return match($metric) {
            'active_incidents' => Incident::whereIn('status', ['open', 'in_progress'])->count(),
            'queue_length' => Incident::whereNull('assigned_to')->count(),
            'sla_at_risk' => count($this->slaAnalytics->getBreachRisk()['at_risk']),
            'online_users' => User::where('last_activity', '>', now()->subMinutes(5))->count(),
            'response_times' => $this->incidentAnalytics->getResponseTimes(['start_date' => now()->subHour(), 'end_date' => now()]),
            default => null
        };
    }

    /**
     * Get drill-down data
     */
    protected function getDrilldownData($metric, $dimension, $value, $startDate, $endDate)
    {
        // This would implement specific drill-down logic
        // For now, returning sample structure
        return [
            'details' => [],
            'summary' => [],
            'related_metrics' => []
        ];
    }
}