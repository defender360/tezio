<?php

namespace App\Services\Analytics;

use App\Models\Incident;
use App\Models\Change;
use App\Models\Problem;
use App\Models\ServiceRequest;
use App\Models\SLA;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardService
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
     * Get executive dashboard data
     */
    public function getExecutiveDashboard($filters = [])
    {
        $cacheKey = 'executive_dashboard_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            return [
                'kpis' => $this->getExecutiveKPIs($filters),
                'trends' => $this->getExecutiveTrends($filters),
                'distributions' => $this->getExecutiveDistributions($filters),
                'top_issues' => $this->getTopIssues($filters),
                'team_performance' => $this->getTeamPerformance($filters),
                'sla_overview' => $this->slaAnalytics->getOverview($filters),
                'change_success_rate' => $this->changeAnalytics->getSuccessRate($filters),
                'cost_analysis' => $this->getCostAnalysis($filters),
                'risk_assessment' => $this->getRiskAssessment($filters)
            ];
        });
    }

    /**
     * Get operational dashboard data
     */
    public function getOperationalDashboard($filters = [])
    {
        $cacheKey = 'operational_dashboard_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            return [
                'active_incidents' => $this->incidentAnalytics->getActiveIncidents($filters),
                'pending_changes' => $this->changeAnalytics->getPendingChanges($filters),
                'workload_distribution' => $this->workloadAnalytics->getDistribution($filters),
                'queue_metrics' => $this->getQueueMetrics($filters),
                'response_times' => $this->getResponseTimes($filters),
                'escalations' => $this->getEscalations($filters),
                'service_health' => $this->getServiceHealth($filters),
                'real_time_activity' => $this->getRealTimeActivity($filters)
            ];
        });
    }

    /**
     * Get team dashboard data
     */
    public function getTeamDashboard($teamId, $filters = [])
    {
        $cacheKey = "team_dashboard_{$teamId}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($teamId, $filters) {
            return [
                'team_metrics' => $this->workloadAnalytics->getTeamMetrics($teamId, $filters),
                'individual_performance' => $this->getIndividualPerformance($teamId, $filters),
                'workload_balance' => $this->workloadAnalytics->getTeamWorkloadBalance($teamId, $filters),
                'skill_matrix' => $this->getSkillMatrix($teamId, $filters),
                'training_needs' => $this->getTrainingNeeds($teamId, $filters),
                'team_sla_performance' => $this->slaAnalytics->getTeamPerformance($teamId, $filters)
            ];
        });
    }

    /**
     * Get executive KPIs
     */
    protected function getExecutiveKPIs($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();
        $previousStartDate = Carbon::parse($startDate)->subMonth();
        $previousEndDate = Carbon::parse($endDate)->subMonth();

        return [
            'total_tickets' => [
                'current' => $this->getTotalTickets($startDate, $endDate),
                'previous' => $this->getTotalTickets($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->getTotalTickets($startDate, $endDate),
                    $this->getTotalTickets($previousStartDate, $previousEndDate)
                )
            ],
            'resolution_rate' => [
                'current' => $this->getResolutionRate($startDate, $endDate),
                'previous' => $this->getResolutionRate($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->getResolutionRate($startDate, $endDate),
                    $this->getResolutionRate($previousStartDate, $previousEndDate)
                )
            ],
            'avg_resolution_time' => [
                'current' => $this->getAvgResolutionTime($startDate, $endDate),
                'previous' => $this->getAvgResolutionTime($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->getAvgResolutionTime($startDate, $endDate),
                    $this->getAvgResolutionTime($previousStartDate, $previousEndDate)
                )
            ],
            'customer_satisfaction' => [
                'current' => $this->getCustomerSatisfaction($startDate, $endDate),
                'previous' => $this->getCustomerSatisfaction($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->getCustomerSatisfaction($startDate, $endDate),
                    $this->getCustomerSatisfaction($previousStartDate, $previousEndDate)
                )
            ],
            'sla_compliance' => [
                'current' => $this->slaAnalytics->getComplianceRate($startDate, $endDate),
                'previous' => $this->slaAnalytics->getComplianceRate($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->slaAnalytics->getComplianceRate($startDate, $endDate),
                    $this->slaAnalytics->getComplianceRate($previousStartDate, $previousEndDate)
                )
            ],
            'cost_per_ticket' => [
                'current' => $this->getCostPerTicket($startDate, $endDate),
                'previous' => $this->getCostPerTicket($previousStartDate, $previousEndDate),
                'change_percent' => $this->calculateChangePercent(
                    $this->getCostPerTicket($startDate, $endDate),
                    $this->getCostPerTicket($previousStartDate, $previousEndDate)
                )
            ]
        ];
    }

    /**
     * Get executive trends
     */
    protected function getExecutiveTrends($filters = [])
    {
        $period = $filters['period'] ?? 'daily';
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'ticket_volume' => $this->getTicketVolumeTrend($startDate, $endDate, $period),
            'resolution_time' => $this->getResolutionTimeTrend($startDate, $endDate, $period),
            'sla_compliance' => $this->slaAnalytics->getComplianceTrend($startDate, $endDate, $period),
            'cost_trend' => $this->getCostTrend($startDate, $endDate, $period),
            'satisfaction_trend' => $this->getSatisfactionTrend($startDate, $endDate, $period)
        ];
    }

    /**
     * Get executive distributions
     */
    protected function getExecutiveDistributions($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'by_category' => $this->getDistributionByCategory($startDate, $endDate),
            'by_priority' => $this->getDistributionByPriority($startDate, $endDate),
            'by_service' => $this->getDistributionByService($startDate, $endDate),
            'by_location' => $this->getDistributionByLocation($startDate, $endDate),
            'by_channel' => $this->getDistributionByChannel($startDate, $endDate)
        ];
    }

    /**
     * Get top issues
     */
    protected function getTopIssues($filters = [])
    {
        $limit = $filters['limit'] ?? 10;
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('incidents')
            ->select('category', DB::raw('COUNT(*) as count'), DB::raw('AVG(resolution_time) as avg_resolution_time'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get team performance
     */
    protected function getTeamPerformance($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('users')
            ->leftJoin('incidents', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'incidents.assigned_to')
                    ->whereBetween('incidents.created_at', [$startDate, $endDate]);
            })
            ->select(
                'users.id',
                'users.name',
                'users.team_id',
                DB::raw('COUNT(incidents.id) as tickets_handled'),
                DB::raw('AVG(incidents.resolution_time) as avg_resolution_time'),
                DB::raw('SUM(CASE WHEN incidents.sla_met = true THEN 1 ELSE 0 END) / COUNT(incidents.id) * 100 as sla_compliance')
            )
            ->groupBy('users.id', 'users.name', 'users.team_id')
            ->get();
    }

    /**
     * Get cost analysis
     */
    protected function getCostAnalysis($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'total_cost' => $this->getTotalCost($startDate, $endDate),
            'cost_by_category' => $this->getCostByCategory($startDate, $endDate),
            'cost_by_priority' => $this->getCostByPriority($startDate, $endDate),
            'cost_savings' => $this->getCostSavings($startDate, $endDate),
            'roi_metrics' => $this->getROIMetrics($startDate, $endDate)
        ];
    }

    /**
     * Get risk assessment
     */
    protected function getRiskAssessment($filters = [])
    {
        return [
            'critical_incidents' => $this->getCriticalIncidents($filters),
            'sla_breach_risk' => $this->slaAnalytics->getBreachRisk($filters),
            'change_failure_risk' => $this->changeAnalytics->getFailureRisk($filters),
            'resource_constraints' => $this->workloadAnalytics->getResourceConstraints($filters),
            'compliance_risks' => $this->getComplianceRisks($filters)
        ];
    }

    /**
     * Get queue metrics
     */
    protected function getQueueMetrics($filters = [])
    {
        return [
            'unassigned_tickets' => $this->getUnassignedTickets($filters),
            'queue_length' => $this->getQueueLength($filters),
            'average_wait_time' => $this->getAverageWaitTime($filters),
            'queue_distribution' => $this->getQueueDistribution($filters)
        ];
    }

    /**
     * Get response times
     */
    protected function getResponseTimes($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('incidents')
            ->select(
                DB::raw('AVG(first_response_time) as avg_first_response'),
                DB::raw('MIN(first_response_time) as min_first_response'),
                DB::raw('MAX(first_response_time) as max_first_response'),
                DB::raw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY first_response_time) as median_first_response'),
                DB::raw('PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY first_response_time) as p95_first_response')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();
    }

    /**
     * Get escalations
     */
    protected function getEscalations($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'total_escalations' => $this->getTotalEscalations($startDate, $endDate),
            'escalation_reasons' => $this->getEscalationReasons($startDate, $endDate),
            'escalation_rate' => $this->getEscalationRate($startDate, $endDate),
            'escalation_trend' => $this->getEscalationTrend($startDate, $endDate)
        ];
    }

    /**
     * Get service health
     */
    protected function getServiceHealth($filters = [])
    {
        return [
            'service_availability' => $this->getServiceAvailability($filters),
            'service_incidents' => $this->getServiceIncidents($filters),
            'service_performance' => $this->getServicePerformance($filters),
            'service_dependencies' => $this->getServiceDependencies($filters)
        ];
    }

    /**
     * Get real-time activity
     */
    protected function getRealTimeActivity($filters = [])
    {
        $minutes = $filters['minutes'] ?? 60;

        return [
            'active_users' => $this->getActiveUsers($minutes),
            'recent_tickets' => $this->getRecentTickets($minutes),
            'recent_updates' => $this->getRecentUpdates($minutes),
            'activity_heatmap' => $this->getActivityHeatmap($minutes)
        ];
    }

    /**
     * Helper method to calculate percentage change
     */
    protected function calculateChangePercent($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get total tickets count
     */
    protected function getTotalTickets($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])->count() +
               Change::whereBetween('created_at', [$startDate, $endDate])->count() +
               Problem::whereBetween('created_at', [$startDate, $endDate])->count() +
               ServiceRequest::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get resolution rate
     */
    protected function getResolutionRate($startDate, $endDate)
    {
        $total = Incident::whereBetween('created_at', [$startDate, $endDate])->count();
        $resolved = Incident::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'resolved')
            ->count();

        return $total > 0 ? round(($resolved / $total) * 100, 2) : 0;
    }

    /**
     * Get average resolution time
     */
    protected function getAvgResolutionTime($startDate, $endDate)
    {
        return Incident::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, resolved_at)'));
    }

    /**
     * Get customer satisfaction score
     */
    protected function getCustomerSatisfaction($startDate, $endDate)
    {
        // Placeholder for customer satisfaction calculation
        // This would typically come from survey data
        return rand(80, 95);
    }

    /**
     * Get cost per ticket
     */
    protected function getCostPerTicket($startDate, $endDate)
    {
        // Placeholder for cost calculation
        // This would involve labor costs, tool costs, etc.
        return rand(15, 45);
    }

    /**
     * Additional helper methods would be implemented here...
     */
}