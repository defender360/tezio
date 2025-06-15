<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Incident;
use App\Models\Change;
use App\Models\Problem;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkloadAnalytics
{
    /**
     * Get workload distribution across teams and individuals
     */
    public function getDistribution($filters = [])
    {
        return [
            'by_team' => $this->getTeamWorkloadDistribution($filters),
            'by_individual' => $this->getIndividualWorkloadDistribution($filters),
            'by_ticket_type' => $this->getWorkloadByTicketType($filters),
            'capacity_analysis' => $this->getCapacityAnalysis($filters),
            'balance_score' => $this->calculateWorkloadBalance($filters)
        ];
    }

    /**
     * Get team-specific metrics
     */
    public function getTeamMetrics($teamId, $filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'overview' => $this->getTeamOverview($teamId, $startDate, $endDate),
            'productivity' => $this->getTeamProductivity($teamId, $startDate, $endDate),
            'efficiency' => $this->getTeamEfficiency($teamId, $startDate, $endDate),
            'utilization' => $this->getTeamUtilization($teamId, $startDate, $endDate),
            'trends' => $this->getTeamTrends($teamId, $startDate, $endDate)
        ];
    }

    /**
     * Get team workload balance
     */
    public function getTeamWorkloadBalance($teamId, $filters = [])
    {
        $members = User::where('team_id', $teamId)->get();
        $workloads = [];

        foreach ($members as $member) {
            $workloads[] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role
                ],
                'current_load' => $this->getUserCurrentLoad($member->id),
                'capacity' => $this->getUserCapacity($member->id),
                'utilization_rate' => $this->getUserUtilizationRate($member->id),
                'tickets_by_priority' => $this->getUserTicketsByPriority($member->id),
                'average_resolution_time' => $this->getUserAverageResolutionTime($member->id)
            ];
        }

        return [
            'members' => $workloads,
            'balance_index' => $this->calculateBalanceIndex($workloads),
            'recommendations' => $this->getBalancingRecommendations($workloads)
        ];
    }

    /**
     * Get resource constraints
     */
    public function getResourceConstraints($filters = [])
    {
        return [
            'overloaded_users' => $this->getOverloadedUsers($filters),
            'skill_gaps' => $this->getSkillGaps($filters),
            'bottlenecks' => $this->getBottlenecks($filters),
            'peak_periods' => $this->getPeakPeriods($filters),
            'recommendations' => $this->getResourceRecommendations($filters)
        ];
    }

    /**
     * Get team workload distribution
     */
    protected function getTeamWorkloadDistribution($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('users')
            ->leftJoin('incidents', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'incidents.assigned_to')
                    ->whereBetween('incidents.created_at', [$startDate, $endDate])
                    ->whereNotIn('incidents.status', ['resolved', 'closed']);
            })
            ->select(
                'users.team_id',
                DB::raw('COUNT(DISTINCT users.id) as team_size'),
                DB::raw('COUNT(incidents.id) as active_tickets'),
                DB::raw('AVG(incidents.priority_score) as avg_priority_score'),
                DB::raw('SUM(incidents.estimated_hours) as total_estimated_hours')
            )
            ->groupBy('users.team_id')
            ->get()
            ->map(function ($team) {
                $team->avg_tickets_per_member = $team->team_size > 0 
                    ? round($team->active_tickets / $team->team_size, 2) 
                    : 0;
                return $team;
            });
    }

    /**
     * Get individual workload distribution
     */
    protected function getIndividualWorkloadDistribution($filters = [])
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subMonth();
        $endDate = $filters['end_date'] ?? Carbon::now();

        return DB::table('users')
            ->leftJoin('incidents', function ($join) {
                $join->on('users.id', '=', 'incidents.assigned_to')
                    ->whereNotIn('incidents.status', ['resolved', 'closed']);
            })
            ->select(
                'users.id',
                'users.name',
                'users.team_id',
                DB::raw('COUNT(incidents.id) as active_tickets'),
                DB::raw('SUM(CASE WHEN incidents.priority = "critical" THEN 1 ELSE 0 END) as critical_tickets'),
                DB::raw('SUM(CASE WHEN incidents.priority = "high" THEN 1 ELSE 0 END) as high_tickets'),
                DB::raw('SUM(incidents.estimated_hours) as total_estimated_hours')
            )
            ->groupBy('users.id', 'users.name', 'users.team_id')
            ->orderByDesc('active_tickets')
            ->get();
    }

    /**
     * Get workload by ticket type
     */
    protected function getWorkloadByTicketType($filters = [])
    {
        $results = [];
        
        // Incidents
        $results['incidents'] = DB::table('incidents')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("open", "in_progress") THEN 1 ELSE 0 END) as active'),
                DB::raw('AVG(estimated_hours) as avg_estimated_hours')
            )
            ->first();

        // Changes
        $results['changes'] = DB::table('changes')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("pending", "scheduled", "in_progress") THEN 1 ELSE 0 END) as active'),
                DB::raw('AVG(estimated_hours) as avg_estimated_hours')
            )
            ->first();

        // Problems
        $results['problems'] = DB::table('problems')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("open", "investigating") THEN 1 ELSE 0 END) as active'),
                DB::raw('AVG(estimated_hours) as avg_estimated_hours')
            )
            ->first();

        // Service Requests
        $results['service_requests'] = DB::table('service_requests')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("open", "in_progress") THEN 1 ELSE 0 END) as active'),
                DB::raw('AVG(estimated_hours) as avg_estimated_hours')
            )
            ->first();

        return $results;
    }

    /**
     * Get capacity analysis
     */
    protected function getCapacityAnalysis($filters = [])
    {
        $users = User::where('active', true)->get();
        $totalCapacity = 0;
        $totalUtilized = 0;

        foreach ($users as $user) {
            $capacity = $this->getUserCapacity($user->id);
            $utilized = $this->getUserUtilizedHours($user->id);
            
            $totalCapacity += $capacity;
            $totalUtilized += $utilized;
        }

        return [
            'total_capacity_hours' => $totalCapacity,
            'total_utilized_hours' => $totalUtilized,
            'utilization_rate' => $totalCapacity > 0 ? round(($totalUtilized / $totalCapacity) * 100, 2) : 0,
            'available_hours' => $totalCapacity - $totalUtilized,
            'capacity_by_team' => $this->getCapacityByTeam(),
            'capacity_forecast' => $this->getCapacityForecast()
        ];
    }

    /**
     * Get team overview
     */
    protected function getTeamOverview($teamId, $startDate, $endDate)
    {
        return DB::table('users')
            ->leftJoin('incidents', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'incidents.assigned_to')
                    ->whereBetween('incidents.created_at', [$startDate, $endDate]);
            })
            ->where('users.team_id', $teamId)
            ->select(
                DB::raw('COUNT(DISTINCT users.id) as team_members'),
                DB::raw('COUNT(incidents.id) as total_tickets'),
                DB::raw('SUM(CASE WHEN incidents.status = "resolved" THEN 1 ELSE 0 END) as resolved_tickets'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, incidents.created_at, incidents.resolved_at)) as avg_resolution_hours'),
                DB::raw('SUM(CASE WHEN incidents.sla_met = true THEN 1 ELSE 0 END) / COUNT(incidents.id) * 100 as sla_compliance')
            )
            ->first();
    }

    /**
     * Get team productivity metrics
     */
    protected function getTeamProductivity($teamId, $startDate, $endDate)
    {
        $periods = $this->generatePeriods($startDate, $endDate, 'week');
        $productivity = [];

        foreach ($periods as $period) {
            $metrics = DB::table('users')
                ->leftJoin('incidents', function ($join) use ($period) {
                    $join->on('users.id', '=', 'incidents.assigned_to')
                        ->whereBetween('incidents.resolved_at', [$period['start'], $period['end']]);
                })
                ->where('users.team_id', $teamId)
                ->select(
                    DB::raw('COUNT(DISTINCT incidents.id) as tickets_resolved'),
                    DB::raw('SUM(incidents.complexity_score) as total_complexity'),
                    DB::raw('COUNT(DISTINCT users.id) as active_members')
                )
                ->first();

            $productivity[] = [
                'period' => $period['label'],
                'tickets_resolved' => $metrics->tickets_resolved,
                'productivity_score' => $metrics->active_members > 0 
                    ? round($metrics->tickets_resolved / $metrics->active_members, 2) 
                    : 0,
                'complexity_handled' => $metrics->total_complexity
            ];
        }

        return $productivity;
    }

    /**
     * Get team efficiency metrics
     */
    protected function getTeamEfficiency($teamId, $startDate, $endDate)
    {
        return [
            'first_response_time' => $this->getTeamFirstResponseTime($teamId, $startDate, $endDate),
            'resolution_time' => $this->getTeamResolutionTime($teamId, $startDate, $endDate),
            'rework_rate' => $this->getTeamReworkRate($teamId, $startDate, $endDate),
            'escalation_rate' => $this->getTeamEscalationRate($teamId, $startDate, $endDate),
            'automation_rate' => $this->getTeamAutomationRate($teamId, $startDate, $endDate)
        ];
    }

    /**
     * Get user current load
     */
    protected function getUserCurrentLoad($userId)
    {
        return [
            'open_incidents' => Incident::where('assigned_to', $userId)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'pending_changes' => Change::where('assigned_to', $userId)
                ->whereIn('status', ['pending', 'scheduled'])
                ->count(),
            'active_problems' => Problem::where('assigned_to', $userId)
                ->whereIn('status', ['open', 'investigating'])
                ->count(),
            'service_requests' => ServiceRequest::where('assigned_to', $userId)
                ->whereIn('status', ['open', 'in_progress'])
                ->count()
        ];
    }

    /**
     * Get user capacity (in hours per week)
     */
    protected function getUserCapacity($userId)
    {
        // Base capacity: 40 hours per week
        $baseCapacity = 40;
        
        // Adjust for meetings, breaks, etc. (typically 70-80% efficiency)
        $efficiency = 0.75;
        
        // Get user's custom capacity if set
        $user = User::find($userId);
        if ($user && $user->weekly_capacity) {
            return $user->weekly_capacity * $efficiency;
        }
        
        return $baseCapacity * $efficiency;
    }

    /**
     * Get user utilization rate
     */
    protected function getUserUtilizationRate($userId)
    {
        $capacity = $this->getUserCapacity($userId);
        $utilized = $this->getUserUtilizedHours($userId);
        
        return $capacity > 0 ? round(($utilized / $capacity) * 100, 2) : 0;
    }

    /**
     * Get user utilized hours
     */
    protected function getUserUtilizedHours($userId)
    {
        $incidents = Incident::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->sum('estimated_hours');
            
        $changes = Change::where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'scheduled', 'in_progress'])
            ->sum('estimated_hours');
            
        $problems = Problem::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'investigating'])
            ->sum('estimated_hours');
            
        $serviceRequests = ServiceRequest::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->sum('estimated_hours');
            
        return $incidents + $changes + $problems + $serviceRequests;
    }

    /**
     * Calculate workload balance index
     */
    protected function calculateBalanceIndex($workloads)
    {
        if (count($workloads) < 2) return 100;

        $utilizationRates = array_column($workloads, 'utilization_rate');
        $mean = array_sum($utilizationRates) / count($utilizationRates);
        
        $variance = 0;
        foreach ($utilizationRates as $rate) {
            $variance += pow($rate - $mean, 2);
        }
        $standardDeviation = sqrt($variance / count($utilizationRates));
        
        // Convert to 0-100 scale where 100 is perfectly balanced
        $balanceIndex = max(0, 100 - ($standardDeviation * 2));
        
        return round($balanceIndex, 2);
    }

    /**
     * Get overloaded users
     */
    protected function getOverloadedUsers($filters = [])
    {
        $threshold = $filters['threshold'] ?? 85; // 85% utilization
        
        return User::where('active', true)
            ->get()
            ->map(function ($user) {
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'team_id' => $user->team_id
                    ],
                    'utilization_rate' => $this->getUserUtilizationRate($user->id),
                    'current_load' => $this->getUserCurrentLoad($user->id),
                    'estimated_hours' => $this->getUserUtilizedHours($user->id)
                ];
            })
            ->filter(function ($item) use ($threshold) {
                return $item['utilization_rate'] > $threshold;
            })
            ->sortByDesc('utilization_rate')
            ->values();
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