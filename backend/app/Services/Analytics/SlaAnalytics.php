<?php

namespace App\Services\Analytics;

use App\Models\SLA;
use App\Models\Incident;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SlaAnalytics
{
    /**
     * Get SLA compliance overview
     */
    public function getOverview($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'overall_compliance' => $this->getComplianceRate($startDate, $endDate),
            'compliance_by_priority' => $this->getComplianceByPriority($startDate, $endDate),
            'compliance_by_service' => $this->getComplianceByService($startDate, $endDate),
            'breach_analysis' => $this->getBreachAnalysis($startDate, $endDate),
            'trending' => $this->getComplianceTrend($startDate, $endDate, 'day')
        ];
    }

    /**
     * Get overall SLA compliance rate
     */
    public function getComplianceRate($startDate, $endDate)
    {
        $incidents = DB::table('incidents')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('sla_id')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) as met')
            ->first();

        $serviceRequests = DB::table('service_requests')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('sla_id')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) as met')
            ->first();

        $total = $incidents->total + $serviceRequests->total;
        $met = $incidents->met + $serviceRequests->met;

        return $total > 0 ? round(($met / $total) * 100, 2) : 0;
    }

    /**
     * Get SLA compliance by priority
     */
    public function getComplianceByPriority($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'priority',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) as met'),
                DB::raw('SUM(CASE WHEN sla_met = false THEN 1 ELSE 0 END) as breached'),
                DB::raw('AVG(CASE WHEN sla_met = true THEN response_time ELSE NULL END) as avg_response_time_met'),
                DB::raw('AVG(CASE WHEN sla_met = false THEN response_time ELSE NULL END) as avg_response_time_breached')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('sla_id')
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->get()
            ->map(function ($item) {
                $item->compliance_rate = $item->total > 0 ? round(($item->met / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Get SLA compliance by service
     */
    public function getComplianceByService($startDate, $endDate)
    {
        return DB::table('incidents')
            ->join('slas', 'incidents.sla_id', '=', 'slas.id')
            ->select(
                'slas.service_name',
                DB::raw('COUNT(incidents.id) as total'),
                DB::raw('SUM(CASE WHEN incidents.sla_met = true THEN 1 ELSE 0 END) as met'),
                DB::raw('AVG(incidents.resolution_time) as avg_resolution_time')
            )
            ->whereBetween('incidents.created_at', [$startDate, $endDate])
            ->groupBy('slas.service_name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                $item->compliance_rate = $item->total > 0 ? round(($item->met / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Get SLA breach analysis
     */
    public function getBreachAnalysis($startDate, $endDate)
    {
        return [
            'breach_reasons' => $this->getBreachReasons($startDate, $endDate),
            'breach_by_time' => $this->getBreachByTime($startDate, $endDate),
            'breach_severity' => $this->getBreachSeverity($startDate, $endDate),
            'repeat_breaches' => $this->getRepeatBreaches($startDate, $endDate)
        ];
    }

    /**
     * Get SLA compliance trend
     */
    public function getComplianceTrend($startDate, $endDate, $groupBy = 'day')
    {
        $dateFormat = $this->getDateFormat($groupBy);

        return DB::table('incidents')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) as met'),
                DB::raw('SUM(CASE WHEN sla_met = false THEN 1 ELSE 0 END) as breached')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('sla_id')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                $item->compliance_rate = $item->total > 0 ? round(($item->met / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Get SLA breach risk assessment
     */
    public function getBreachRisk($filters = [])
    {
        $activeIncidents = Incident::with('sla')
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('sla_id')
            ->get();

        $atRisk = [];
        $approaching = [];
        $safe = [];

        foreach ($activeIncidents as $incident) {
            $timeElapsed = Carbon::now()->diffInMinutes($incident->created_at);
            $slaTarget = $this->getSLATarget($incident);
            $percentageUsed = ($timeElapsed / $slaTarget) * 100;

            if ($percentageUsed >= 90) {
                $atRisk[] = $this->formatRiskItem($incident, $percentageUsed);
            } elseif ($percentageUsed >= 75) {
                $approaching[] = $this->formatRiskItem($incident, $percentageUsed);
            } else {
                $safe[] = $this->formatRiskItem($incident, $percentageUsed);
            }
        }

        return [
            'at_risk' => $atRisk,
            'approaching' => $approaching,
            'safe' => $safe,
            'summary' => [
                'at_risk_count' => count($atRisk),
                'approaching_count' => count($approaching),
                'safe_count' => count($safe),
                'total_monitored' => count($activeIncidents)
            ]
        ];
    }

    /**
     * Get team SLA performance
     */
    public function getTeamPerformance($teamId, $filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('incidents')
            ->join('users', 'incidents.assigned_to', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(incidents.id) as total_tickets'),
                DB::raw('SUM(CASE WHEN incidents.sla_met = true THEN 1 ELSE 0 END) as sla_met'),
                DB::raw('AVG(incidents.response_time) as avg_response_time'),
                DB::raw('AVG(incidents.resolution_time) as avg_resolution_time')
            )
            ->where('users.team_id', $teamId)
            ->whereBetween('incidents.created_at', [$startDate, $endDate])
            ->whereNotNull('incidents.sla_id')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(function ($member) {
                $member->compliance_rate = $member->total_tickets > 0 
                    ? round(($member->sla_met / $member->total_tickets) * 100, 2) 
                    : 0;
                return $member;
            });
    }

    /**
     * Get SLA performance by time of day
     */
    public function getPerformanceByTimeOfDay($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) as met'),
                DB::raw('AVG(response_time) as avg_response_time')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('sla_id')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                $item->compliance_rate = $item->total > 0 ? round(($item->met / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Get SLA target achievements
     */
    public function getTargetAchievements($slaId, $startDate, $endDate)
    {
        $sla = SLA::find($slaId);
        if (!$sla) return null;

        return [
            'response_time' => $this->getResponseTimeAchievement($slaId, $startDate, $endDate),
            'resolution_time' => $this->getResolutionTimeAchievement($slaId, $startDate, $endDate),
            'availability' => $this->getAvailabilityAchievement($slaId, $startDate, $endDate),
            'escalation' => $this->getEscalationAchievement($slaId, $startDate, $endDate)
        ];
    }

    /**
     * Get breach reasons
     */
    protected function getBreachReasons($startDate, $endDate)
    {
        return DB::table('sla_breaches')
            ->select(
                'breach_reason',
                'breach_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(breach_duration_minutes) as avg_breach_duration')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('breach_reason', 'breach_type')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get breach by time analysis
     */
    protected function getBreachByTime($startDate, $endDate)
    {
        return [
            'by_hour' => DB::table('sla_breaches')
                ->select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
                
            'by_day_of_week' => DB::table('sla_breaches')
                ->select(
                    DB::raw('DAYNAME(created_at) as day'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('day')
                ->orderByRaw('DAYOFWEEK(created_at)')
                ->get()
        ];
    }

    /**
     * Get breach severity analysis
     */
    protected function getBreachSeverity($startDate, $endDate)
    {
        return DB::table('sla_breaches')
            ->join('incidents', 'sla_breaches.incident_id', '=', 'incidents.id')
            ->select(
                DB::raw('CASE 
                    WHEN breach_duration_minutes <= 30 THEN "Minor"
                    WHEN breach_duration_minutes <= 120 THEN "Moderate"
                    WHEN breach_duration_minutes <= 480 THEN "Major"
                    ELSE "Severe"
                END as severity'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(breach_duration_minutes) as avg_duration'),
                DB::raw('SUM(incidents.impact_score) as total_impact')
            )
            ->whereBetween('sla_breaches.created_at', [$startDate, $endDate])
            ->groupBy('severity')
            ->orderByRaw('MIN(breach_duration_minutes)')
            ->get();
    }

    /**
     * Get repeat breaches
     */
    protected function getRepeatBreaches($startDate, $endDate)
    {
        return DB::table('sla_breaches')
            ->select(
                'service_id',
                'category',
                DB::raw('COUNT(*) as breach_count'),
                DB::raw('GROUP_CONCAT(DISTINCT breach_reason) as reasons')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('service_id', 'category')
            ->having('breach_count', '>', 2)
            ->orderByDesc('breach_count')
            ->limit(20)
            ->get();
    }

    /**
     * Get SLA target for incident
     */
    protected function getSLATarget($incident)
    {
        if (!$incident->sla) return 480; // Default 8 hours

        return match($incident->priority) {
            'critical' => $incident->sla->critical_response_minutes,
            'high' => $incident->sla->high_response_minutes,
            'medium' => $incident->sla->medium_response_minutes,
            'low' => $incident->sla->low_response_minutes,
            default => 480
        };
    }

    /**
     * Format risk item
     */
    protected function formatRiskItem($incident, $percentageUsed)
    {
        return [
            'id' => $incident->id,
            'title' => $incident->title,
            'priority' => $incident->priority,
            'assigned_to' => $incident->assignedTo->name ?? 'Unassigned',
            'created_at' => $incident->created_at,
            'time_elapsed' => Carbon::now()->diffInMinutes($incident->created_at),
            'sla_target' => $this->getSLATarget($incident),
            'percentage_used' => round($percentageUsed, 2),
            'time_remaining' => max(0, $this->getSLATarget($incident) - Carbon::now()->diffInMinutes($incident->created_at))
        ];
    }

    /**
     * Get date format based on grouping
     */
    protected function getDateFormat($groupBy)
    {
        return match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };
    }
}