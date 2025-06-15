<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SLA extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'slas';

    protected $fillable = [
        'name',
        'description',
        'type',
        'target_type',
        'business_hours_only',
        'response_time',
        'resolution_time',
        'escalation_time',
        'priority_matrix',
        'excluded_statuses',
        'conditions',
        'penalties',
        'is_active',
        'valid_from',
        'valid_until'
    ];

    protected $casts = [
        'business_hours_only' => 'boolean',
        'is_active' => 'boolean',
        'response_time' => 'integer',
        'resolution_time' => 'integer',
        'escalation_time' => 'integer',
        'priority_matrix' => 'array',
        'excluded_statuses' => 'array',
        'conditions' => 'array',
        'penalties' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime'
    ];

    /**
     * Scope for active SLAs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', now());
                    });
    }

    /**
     * Scope for SLAs by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get SLA assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SLAAssignment::class);
    }

    /**
     * Get SLA metrics
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(SLAMetric::class);
    }

    /**
     * Calculate target time based on priority
     */
    public function calculateTargetTime(string $priority, string $targetType): ?int
    {
        if (!isset($this->priority_matrix[$priority])) {
            return null;
        }

        $matrix = $this->priority_matrix[$priority];
        
        return match($targetType) {
            'response' => $matrix['response_time'] ?? $this->response_time,
            'resolution' => $matrix['resolution_time'] ?? $this->resolution_time,
            'escalation' => $matrix['escalation_time'] ?? $this->escalation_time,
            default => null
        };
    }

    /**
     * Calculate deadline from start time
     */
    public function calculateDeadline(Carbon $startTime, string $priority, string $targetType): ?Carbon
    {
        $targetMinutes = $this->calculateTargetTime($priority, $targetType);
        
        if (!$targetMinutes) {
            return null;
        }

        if ($this->business_hours_only) {
            return $this->addBusinessMinutes($startTime, $targetMinutes);
        }

        return $startTime->copy()->addMinutes($targetMinutes);
    }

    /**
     * Add business minutes to a date
     */
    protected function addBusinessMinutes(Carbon $date, int $minutes): Carbon
    {
        $result = $date->copy();
        $remainingMinutes = $minutes;

        while ($remainingMinutes > 0) {
            // Skip weekends
            if ($result->isWeekend()) {
                $result->nextWeekday();
                $result->setTime(9, 0, 0); // Start of business day
                continue;
            }

            // Check if within business hours (9 AM to 6 PM)
            if ($result->hour < 9) {
                $result->setTime(9, 0, 0);
            } elseif ($result->hour >= 18) {
                $result->addDay()->setTime(9, 0, 0);
                continue;
            }

            // Calculate minutes until end of business day
            $endOfDay = $result->copy()->setTime(18, 0, 0);
            $minutesUntilEOD = $result->diffInMinutes($endOfDay);

            if ($remainingMinutes <= $minutesUntilEOD) {
                $result->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $minutesUntilEOD;
                $result->addDay()->setTime(9, 0, 0);
            }
        }

        return $result;
    }

    /**
     * Check if conditions are met
     */
    public function areConditionsMet(array $data): bool
    {
        if (!$this->conditions || empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($data[$field])) {
                return false;
            }

            $fieldValue = $data[$field];

            $conditionMet = match($operator) {
                '=' => $fieldValue == $value,
                '!=' => $fieldValue != $value,
                '>' => $fieldValue > $value,
                '<' => $fieldValue < $value,
                '>=' => $fieldValue >= $value,
                '<=' => $fieldValue <= $value,
                'in' => in_array($fieldValue, (array)$value),
                'not_in' => !in_array($fieldValue, (array)$value),
                'contains' => str_contains($fieldValue, $value),
                'not_contains' => !str_contains($fieldValue, $value),
                default => false
            };

            if (!$conditionMet) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get applicable SLA for given data
     */
    public static function getApplicableSLA(string $type, array $data): ?self
    {
        return static::active()
            ->ofType($type)
            ->get()
            ->first(function ($sla) use ($data) {
                return $sla->areConditionsMet($data);
            });
    }

    /**
     * Calculate compliance percentage
     */
    public function calculateCompliance(Carbon $from = null, Carbon $to = null): float
    {
        $query = $this->metrics();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = $query->count();
        if ($total === 0) {
            return 100.0;
        }

        $breached = $query->where('is_breached', true)->count();
        
        return round(((($total - $breached) / $total) * 100), 2);
    }
}