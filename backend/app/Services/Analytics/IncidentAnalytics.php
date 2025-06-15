<?php

namespace App\Services\Analytics;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncidentAnalytics
{
    /**
     * Get active incidents with details
     */
    public function getActiveIncidents($filters = [])
    {
        $query = Incident::with(['assignedTo', 'reporter'])
            ->whereNotIn('status', ['resolved', 'closed']);

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get incident trends over time
     */
    public function getTrends($startDate, $endDate, $groupBy = 'day')
    {
        $dateFormat = $this->getDateFormat($groupBy);
        
        return DB::table('incidents')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN priority = "critical" THEN 1 ELSE 0 END) as critical'),
                DB::raw('SUM(CASE WHEN priority = "high" THEN 1 ELSE 0 END) as high'),
                DB::raw('SUM(CASE WHEN priority = "medium" THEN 1 ELSE 0 END) as medium'),
                DB::raw('SUM(CASE WHEN priority = "low" THEN 1 ELSE 0 END) as low'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get incident distribution by category
     */
    public function getCategoryDistribution($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'category',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours'),
                DB::raw('SUM(CASE WHEN sla_met = true THEN 1 ELSE 0 END) / COUNT(*) * 100 as sla_compliance_rate')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get incident metrics by priority
     */
    public function getPriorityMetrics($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'priority',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved'),
                DB::raw('SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open'),
                DB::raw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_first_response_minutes'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->get();
    }

    /**
     * Get top incident categories by volume
     */
    public function getTopCategories($limit = 10, $startDate = null, $endDate = null)
    {
        $query = DB::table('incidents')
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    /**
     * Get MTTR (Mean Time To Resolution) metrics
     */
    public function getMTTRMetrics($startDate, $endDate, $groupBy = 'category')
    {
        return DB::table('incidents')
            ->select(
                $groupBy,
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as mttr_hours'),
                DB::raw('MIN(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as min_resolution_hours'),
                DB::raw('MAX(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as max_resolution_hours'),
                DB::raw('COUNT(*) as incident_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->groupBy($groupBy)
            ->orderBy('mttr_hours')
            ->get();
    }

    /**
     * Get incident aging analysis
     */
    public function getAgingAnalysis()
    {
        return DB::table('incidents')
            ->select(
                DB::raw('CASE 
                    WHEN DATEDIFF(NOW(), created_at) <= 1 THEN "0-1 days"
                    WHEN DATEDIFF(NOW(), created_at) <= 3 THEN "2-3 days"
                    WHEN DATEDIFF(NOW(), created_at) <= 7 THEN "4-7 days"
                    WHEN DATEDIFF(NOW(), created_at) <= 14 THEN "8-14 days"
                    WHEN DATEDIFF(NOW(), created_at) <= 30 THEN "15-30 days"
                    ELSE "30+ days"
                END as age_group'),
                'priority',
                DB::raw('COUNT(*) as count')
            )
            ->whereNotIn('status', ['resolved', 'closed'])
            ->groupBy('age_group', 'priority')
            ->orderByRaw('MIN(DATEDIFF(NOW(), created_at))')
            ->get();
    }

    /**
     * Get reopen rate analysis
     */
    public function getReopenRate($startDate, $endDate)
    {
        $total = Incident::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $reopened = DB::table('incident_histories')
            ->join('incidents', 'incidents.id', '=', 'incident_histories.incident_id')
            ->where('incident_histories.field', 'status')
            ->where('incident_histories.old_value', 'resolved')
            ->where('incident_histories.new_value', 'open')
            ->whereBetween('incidents.created_at', [$startDate, $endDate])
            ->distinct('incident_id')
            ->count('incident_id');

        return [
            'total_incidents' => $total,
            'reopened_incidents' => $reopened,
            'reopen_rate' => $total > 0 ? round(($reopened / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get incident volume forecast
     */
    public function getVolumeForecast($days = 30)
    {
        // Simple moving average forecast
        $historicalData = DB::table('incidents')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate moving average and generate forecast
        $movingAverage = $historicalData->avg('count');
        $trend = $this->calculateTrend($historicalData);

        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecast[] = [
                'date' => Carbon::now()->addDays($i)->format('Y-m-d'),
                'predicted_count' => round($movingAverage + ($trend * $i)),
                'confidence_interval' => [
                    'lower' => round(($movingAverage + ($trend * $i)) * 0.8),
                    'upper' => round(($movingAverage + ($trend * $i)) * 1.2)
                ]
            ];
        }

        return $forecast;
    }

    /**
     * Get root cause analysis
     */
    public function getRootCauseAnalysis($startDate, $endDate)
    {
        return DB::table('incidents')
            ->leftJoin('problems', 'incidents.problem_id', '=', 'problems.id')
            ->select(
                'problems.root_cause',
                DB::raw('COUNT(incidents.id) as incident_count'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, incidents.created_at, incidents.resolved_at)) as avg_resolution_hours'),
                DB::raw('SUM(incidents.impact_score) as total_impact')
            )
            ->whereBetween('incidents.created_at', [$startDate, $endDate])
            ->whereNotNull('problems.root_cause')
            ->groupBy('problems.root_cause')
            ->orderByDesc('incident_count')
            ->get();
    }

    /**
     * Get pattern analysis
     */
    public function getPatternAnalysis($startDate, $endDate)
    {
        return [
            'time_patterns' => $this->getTimePatterns($startDate, $endDate),
            'category_patterns' => $this->getCategoryPatterns($startDate, $endDate),
            'user_patterns' => $this->getUserPatterns($startDate, $endDate),
            'recurring_issues' => $this->getRecurringIssues($startDate, $endDate)
        ];
    }

    /**
     * Get time patterns (hour of day, day of week analysis)
     */
    protected function getTimePatterns($startDate, $endDate)
    {
        return [
            'by_hour' => DB::table('incidents')
                ->select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
                
            'by_day_of_week' => DB::table('incidents')
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
     * Get category patterns
     */
    protected function getCategoryPatterns($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'category',
                'subcategory',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('GROUP_CONCAT(DISTINCT symptom) as common_symptoms')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('category', 'subcategory')
            ->having('frequency', '>', 5)
            ->orderByDesc('frequency')
            ->get();
    }

    /**
     * Get user patterns
     */
    protected function getUserPatterns($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'reporter_id',
                DB::raw('COUNT(*) as incident_count'),
                DB::raw('COUNT(DISTINCT category) as category_variety'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('reporter_id')
            ->having('incident_count', '>', 3)
            ->orderByDesc('incident_count')
            ->limit(20)
            ->get();
    }

    /**
     * Get recurring issues
     */
    protected function getRecurringIssues($startDate, $endDate)
    {
        return DB::table('incidents')
            ->select(
                'title',
                'category',
                DB::raw('COUNT(*) as occurrence_count'),
                DB::raw('GROUP_CONCAT(id) as incident_ids')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('title', 'category')
            ->having('occurrence_count', '>', 2)
            ->orderByDesc('occurrence_count')
            ->limit(20)
            ->get();
    }

    /**
     * Calculate trend from historical data
     */
    protected function calculateTrend($data)
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $i => $point) {
            $sumX += $i;
            $sumY += $point->count;
            $sumXY += $i * $point->count;
            $sumX2 += $i * $i;
        }

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
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