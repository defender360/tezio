<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasWorkflow;
use App\Traits\HasApprovals;
use App\Traits\HasActivities;

class Change extends Model
{
    use HasFactory, SoftDeletes, HasWorkflow, HasApprovals, HasActivities;

    protected $fillable = [
        'change_number',
        'title',
        'description',
        'type',
        'category',
        'priority',
        'impact',
        'risk_level',
        'status',
        'requester_id',
        'assigned_to',
        'change_manager_id',
        'cab_required',
        'cab_date',
        'implementation_plan',
        'rollback_plan',
        'test_plan',
        'communication_plan',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'downtime_required',
        'downtime_duration',
        'affected_services',
        'business_justification',
        'technical_justification',
        'risk_assessment',
        'approval_status',
        'implementation_status',
        'post_implementation_review',
        'lessons_learned',
        'emergency_change',
        'parent_change_id',
        'template_id'
    ];

    protected $casts = [
        'cab_required' => 'boolean',
        'downtime_required' => 'boolean',
        'emergency_change' => 'boolean',
        'affected_services' => 'array',
        'risk_assessment' => 'array',
        'cab_date' => 'datetime',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'downtime_duration' => 'integer'
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($change) {
            if (empty($change->change_number)) {
                $change->change_number = $change->generateChangeNumber();
            }
            
            // Set initial workflow state
            $change->workflow_state = 'draft';
            $change->approval_status = 'pending';
        });
    }

    /**
     * Generate unique change number
     */
    public function generateChangeNumber(): string
    {
        $prefix = $this->emergency_change ? 'ECH' : 'CHG';
        $year = now()->format('Y');
        $lastChange = static::where('change_number', 'like', "{$prefix}-{$year}-%")
                           ->orderBy('id', 'desc')
                           ->first();
        
        if ($lastChange) {
            $lastNumber = intval(substr($lastChange->change_number, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }
        
        return "{$prefix}-{$year}-{$newNumber}";
    }

    /**
     * Scope for changes requiring CAB approval
     */
    public function scopeRequiringCab(Builder $query): Builder
    {
        return $query->where('cab_required', true)
                    ->where('approval_status', 'pending');
    }

    /**
     * Scope for emergency changes
     */
    public function scopeEmergency(Builder $query): Builder
    {
        return $query->where('emergency_change', true);
    }

    /**
     * Scope for scheduled changes
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_start')
                    ->where('status', 'approved');
    }

    /**
     * Get the requester
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the assigned user
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the change manager
     */
    public function changeManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'change_manager_id');
    }

    /**
     * Get the parent change
     */
    public function parentChange(): BelongsTo
    {
        return $this->belongsTo(Change::class, 'parent_change_id');
    }

    /**
     * Get child changes
     */
    public function childChanges(): HasMany
    {
        return $this->hasMany(Change::class, 'parent_change_id');
    }

    /**
     * Get the change template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChangeTemplate::class, 'template_id');
    }

    /**
     * Get related incidents
     */
    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'change_incident')
                    ->withTimestamps();
    }

    /**
     * Get related problems
     */
    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problem::class, 'change_problem')
                    ->withTimestamps();
    }

    /**
     * Get affected configuration items
     */
    public function configurationItems(): BelongsToMany
    {
        return $this->belongsToMany(ConfigurationItem::class, 'change_configuration_item')
                    ->withPivot('impact_type', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get change tasks
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ChangeTask::class)
                    ->orderBy('sequence');
    }

    /**
     * Get change approvals
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Get change attachments
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get change comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Check if change is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }
        
        return $this->scheduled_end && $this->scheduled_end->isPast();
    }

    /**
     * Check if change can be implemented
     */
    public function canBeImplemented(): bool
    {
        return $this->status === 'approved' && 
               $this->approval_status === 'approved' &&
               $this->scheduled_start &&
               $this->scheduled_start->lte(now()->addMinutes(15));
    }

    /**
     * Calculate change duration
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->actual_start && $this->actual_end) {
            return $this->actual_start->diffInMinutes($this->actual_end);
        }
        
        if ($this->scheduled_start && $this->scheduled_end) {
            return $this->scheduled_start->diffInMinutes($this->scheduled_end);
        }
        
        return null;
    }

    /**
     * Get risk level color
     */
    public function getRiskLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }
}