<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasWorkflow;
use App\Traits\HasActivities;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes, HasWorkflow, HasActivities;

    protected $fillable = [
        'request_number',
        'service_item_id',
        'requester_id',
        'requested_for',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'assigned_group_id',
        'category',
        'subcategory',
        'urgency',
        'impact',
        'due_date',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'fulfilment_notes',
        'customer_satisfaction',
        'approval_required',
        'approval_status',
        'estimated_cost',
        'actual_cost',
        'estimated_hours',
        'actual_hours',
        'variables',
        'sla_breach_at',
        'workflow_state'
    ];

    protected $casts = [
        'approval_required' => 'boolean',
        'variables' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'customer_satisfaction' => 'integer',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'sla_breach_at' => 'datetime'
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($serviceRequest) {
            if (empty($serviceRequest->request_number)) {
                $serviceRequest->request_number = $serviceRequest->generateRequestNumber();
            }
            
            // Calculate priority based on urgency and impact
            if ($serviceRequest->urgency && $serviceRequest->impact) {
                $serviceRequest->priority = $serviceRequest->calculatePriority();
            }
            
            // Set workflow state
            $serviceRequest->workflow_state = 'submitted';
        });
    }

    /**
     * Generate unique request number
     */
    public function generateRequestNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $lastRequest = static::where('request_number', 'like', "REQ-{$year}{$month}-%")
                            ->orderBy('id', 'desc')
                            ->first();
        
        if ($lastRequest) {
            $lastNumber = intval(substr($lastRequest->request_number, -5));
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }
        
        return "REQ-{$year}{$month}-{$newNumber}";
    }

    /**
     * Calculate priority based on urgency and impact
     */
    public function calculatePriority(): string
    {
        $matrix = [
            'high' => [
                'high' => 'critical',
                'medium' => 'high',
                'low' => 'medium'
            ],
            'medium' => [
                'high' => 'high',
                'medium' => 'medium',
                'low' => 'low'
            ],
            'low' => [
                'high' => 'medium',
                'medium' => 'low',
                'low' => 'low'
            ]
        ];
        
        return $matrix[$this->urgency][$this->impact] ?? 'medium';
    }

    /**
     * Scope for open requests
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'closed']);
    }

    /**
     * Scope for requests requiring approval
     */
    public function scopeRequiringApproval(Builder $query): Builder
    {
        return $query->where('approval_required', true)
                    ->where('approval_status', 'pending');
    }

    /**
     * Scope for overdue requests
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->whereNull('completed_at');
    }

    /**
     * Get the service catalog item
     */
    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'service_item_id');
    }

    /**
     * Get the requester
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user this request is for
     */
    public function requestedFor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_for');
    }

    /**
     * Get the assigned user
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the assigned group
     */
    public function assignedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'assigned_group_id');
    }

    /**
     * Get request tasks
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ServiceRequestTask::class)
                    ->orderBy('sequence');
    }

    /**
     * Get request approvals
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Get request attachments
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get request comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get request activities
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Check if request is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->completed_at || $this->cancelled_at) {
            return false;
        }
        
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Check if SLA is breached
     */
    public function isSlaBreached(): bool
    {
        if ($this->completed_at || $this->cancelled_at) {
            return false;
        }
        
        return $this->sla_breach_at && $this->sla_breach_at->isPast();
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }
        
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        
        return round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'submitted' => 'blue',
            'in_progress' => 'yellow',
            'pending_approval' => 'orange',
            'on_hold' => 'gray',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }
}