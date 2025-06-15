<?php

namespace App\Services\Analytics;

use App\Models\Change;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChangeAnalytics
{
    /**
     * Get pending changes
     */
    public function getPendingChanges($filters = [])
    {
        $query = Change::with(['requestedBy', 'assignedTo', 'approvals'])
            ->whereIn('status', ['pending', 'awaiting_approval', 'scheduled']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (isset($filters['scheduled_start'])) {
            $query->where('scheduled_start', '>=', $filters['scheduled_start']);
        }

        return $query->orderBy('scheduled_start')
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get change success rate
     */
    public function getSuccessRate($startDate = null, $endDate = null)
    {
        $query = Change::whereIn('status', ['completed', 'failed', 'rolled_back']);

        if ($startDate && $endDate) {
            $query->whereBetween('implemented_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $successful = $query->where('status', 'completed')->count();

        return [
            'total_changes' => $total,
            'successful_changes' => $successful,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get change trends
     */
    public function getTrends($startDate, $endDate, $groupBy = 'week')
    {
        $dateFormat = $this->getDateFormat($groupBy);

        return DB::table('changes')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN type = "standard" THEN 1 ELSE 0 END) as standard'),
                DB::raw('SUM(CASE WHEN type = "normal" THEN 1 ELSE 0 END) as normal'),
                DB::raw('SUM(CASE WHEN type = "emergency" THEN 1 ELSE 0 END) as emergency'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(CASE WHEN status = "rolled_back" THEN 1 ELSE 0 END) as rolled_back')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get change distribution by type
     */
    public function getTypeDistribution($startDate, $endDate)
    {
        return DB::table('changes')
            ->select(
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, scheduled_start, actual_end)) as avg_duration_hours')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('type')
            ->get();
    }

    /**
     * Get change metrics by risk level
     */
    public function getRiskMetrics($startDate, $endDate)
    {
        return DB::table('changes')
            ->select(
                'risk_level',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(CASE WHEN status = "rolled_back" THEN 1 ELSE 0 END) as rolled_back'),
                DB::raw('AVG(approval_time) as avg_approval_hours')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('risk_level')
            ->orderByRaw("FIELD(risk_level, 'high', 'medium', 'low')")
            ->get();
    }

    /**
     * Get change approval metrics
     */
    public function getApprovalMetrics($startDate, $endDate)
    {
        return [
            'approval_times' => $this->getApprovalTimes($startDate, $endDate),
            'approval_rates' => $this->getApprovalRates($startDate, $endDate),
            'cab_metrics' => $this->getCABMetrics($startDate, $endDate),
            'approval_bottlenecks' => $this->getApprovalBottlenecks($startDate, $endDate)
        ];
    }

    /**
     * Get change window utilization
     */
    public function getWindowUtilization($startDate, $endDate)
    {
        return DB::table('changes')
            ->select(
                DB::raw('DAYNAME(scheduled_start) as day'),
                DB::raw('HOUR(scheduled_start) as hour'),
                DB::raw('COUNT(*) as change_count'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end)) as total_minutes')
            )
            ->whereBetween('scheduled_start', [$startDate, $endDate])
            ->groupBy('day', 'hour')
            ->orderByRaw('DAYOFWEEK(scheduled_start), hour')
            ->get();
    }

    /**
     * Get change failure analysis
     */
    public function getFailureAnalysis($startDate, $endDate)
    {
        return [
            'failure_reasons' => $this->getFailureReasons($startDate, $endDate),
            'failure_by_category' => $this->getFailureByCategory($startDate, $endDate),
            'failure_impact' => $this->getFailureImpact($startDate, $endDate),
            'rollback_analysis' => $this->getRollbackAnalysis($startDate, $endDate)
        ];
    }

    /**
     * Get change failure risk assessment
     */
    public function getFailureRisk($filters = [])
    {
        $riskFactors = [];

        // High-risk time windows
        $riskFactors['time_risk'] = $this->getTimeRisk($filters);

        // Category risk based on historical data
        $riskFactors['category_risk'] = $this->getCategoryRisk($filters);

        // Implementer risk based on past performance
        $riskFactors['implementer_risk'] = $this->getImplementerRisk($filters);

        // Complexity risk
        $riskFactors['complexity_risk'] = $this->getComplexityRisk($filters);

        // Overall risk score
        $riskFactors['overall_risk'] = $this->calculateOverallRisk($riskFactors);

        return $riskFactors;
    }

    /**
     * Get change calendar data
     */
    public function getCalendarData($startDate, $endDate)
    {
        return Change::select(
                'id',
                'title',
                'type',
                'risk_level',
                'status',
                'scheduled_start',
                'scheduled_end',
                'assigned_to'
            )
            ->whereBetween('scheduled_start', [$startDate, $endDate])
            ->orWhereBetween('scheduled_end', [$startDate, $endDate])
            ->orderBy('scheduled_start')
            ->get()
            ->map(function ($change) {
                return [
                    'id' => $change->id,
                    'title' => $change->title,
                    'start' => $change->scheduled_start,
                    'end' => $change->scheduled_end,
                    'color' => $this->getChangeColor($change->type, $change->risk_level),
                    'extendedProps' => [
                        'type' => $change->type,
                        'risk_level' => $change->risk_level,
                        'status' => $change->status,
                        'assigned_to' => $change->assigned_to
                    ]
                ];
            });
    }

    /**
     * Get change velocity metrics
     */
    public function getVelocityMetrics($startDate, $endDate)
    {
        $periods = $this->generatePeriods($startDate, $endDate, 'week');
        $velocityData = [];

        foreach ($periods as $period) {
            $changes = Change::whereBetween('implemented_at', [$period['start'], $period['end']])
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful')
                ->first();

            $velocityData[] = [
                'period' => $period['label'],
                'total_changes' => $changes->total,
                'successful_changes' => $changes->successful,
                'velocity' => $changes->total / 7 // Changes per day
            ];
        }

        return $velocityData;
    }

    /**
     * Get change impact analysis
     */
    public function getImpactAnalysis($changeId)
    {
        $change = Change::find($changeId);
        if (!$change) return null;

        return [
            'affected_services' => $this->getAffectedServices($changeId),
            'affected_users' => $this->getAffectedUsers($changeId),
            'related_incidents' => $this->getRelatedIncidents($changeId),
            'downstream_changes' => $this->getDownstreamChanges($changeId),
            'risk_assessment' => $this->assessChangeRisk($change)
        ];
    }

    /**
     * Get approval times analysis
     */
    protected function getApprovalTimes($startDate, $endDate)
    {
        return DB::table('changes')
            ->join('change_approvals', 'changes.id', '=', 'change_approvals.change_id')
            ->select(
                'changes.type',
                'changes.risk_level',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, change_approvals.created_at, change_approvals.approved_at)) as avg_approval_hours'),
                DB::raw('MIN(TIMESTAMPDIFF(HOUR, change_approvals.created_at, change_approvals.approved_at)) as min_approval_hours'),
                DB::raw('MAX(TIMESTAMPDIFF(HOUR, change_approvals.created_at, change_approvals.approved_at)) as max_approval_hours')
            )
            ->whereBetween('changes.created_at', [$startDate, $endDate])
            ->whereNotNull('change_approvals.approved_at')
            ->groupBy('changes.type', 'changes.risk_level')
            ->get();
    }

    /**
     * Get approval rates
     */
    protected function getApprovalRates($startDate, $endDate)
    {
        return DB::table('change_approvals')
            ->join('changes', 'changes.id', '=', 'change_approvals.change_id')
            ->select(
                'change_approvals.approver_role',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN change_approvals.status = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN change_approvals.status = "rejected" THEN 1 ELSE 0 END) as rejected')
            )
            ->whereBetween('changes.created_at', [$startDate, $endDate])
            ->groupBy('change_approvals.approver_role')
            ->get();
    }

    /**
     * Get CAB meeting metrics
     */
    protected function getCABMetrics($startDate, $endDate)
    {
        return DB::table('cab_meetings')
            ->select(
                DB::raw('COUNT(DISTINCT id) as total_meetings'),
                DB::raw('COUNT(DISTINCT change_id) as changes_reviewed'),
                DB::raw('AVG(duration_minutes) as avg_duration'),
                DB::raw('SUM(CASE WHEN decision = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN decision = "rejected" THEN 1 ELSE 0 END) as rejected'),
                DB::raw('SUM(CASE WHEN decision = "deferred" THEN 1 ELSE 0 END) as deferred')
            )
            ->whereBetween('meeting_date', [$startDate, $endDate])
            ->first();
    }

    /**
     * Get failure reasons
     */
    protected function getFailureReasons($startDate, $endDate)
    {
        return DB::table('changes')
            ->select(
                'failure_reason',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(impact_score) as avg_impact')
            )
            ->whereIn('status', ['failed', 'rolled_back'])
            ->whereBetween('implemented_at', [$startDate, $endDate])
            ->whereNotNull('failure_reason')
            ->groupBy('failure_reason')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get date format based on grouping
     */
    protected function getDateFormat($groupBy)
    {
        return match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };
    }

    /**
     * Get change color based on type and risk
     */
    protected function getChangeColor($type, $riskLevel)
    {
        if ($type === 'emergency') return '#dc2626'; // red
        if ($riskLevel === 'high') return '#f59e0b'; // amber
        if ($type === 'standard') return '#10b981'; // green
        return '#3b82f6'; // blue
    }

    /**
     * Generate date periods
     */
    protected function generatePeriods($startDate, $endDate, $interval = 'week')
    {
        $periods = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $periodEnd = $current->copy()->add($interval, 1)->subDay();
            $periods[] = [
                'start' => $current->format('Y-m-d'),
                'end' => $periodEnd->format('Y-m-d'),
                'label' => $current->format('Y-m-d') . ' - ' . $periodEnd->format('Y-m-d')
            ];
            $current->add($interval, 1);
        }

        return $periods;
    }
}