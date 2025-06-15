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
use App\Traits\HasActivities;

class Problem extends Model
{
    use HasFactory, SoftDeletes, HasActivities;

    protected $fillable = [
        'problem_number',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'root_cause',
        'symptoms',
        'impact_description',
        'workaround',
        'permanent_solution',
        'assigned_to',
        'assigned_group_id',
        'reported_by',
        'detected_date',
        'resolved_date',
        'closed_date',
        'known_error',
        'known_error_date',
        'resolution_target_date',
        'actual_resolution_date',
        'review_notes',
        'lessons_learned'
    ];

    protected $casts = [
        'symptoms' => 'array',
        'known_error' => 'boolean',
        'detected_date' => 'datetime',
        'resolved_date' => 'datetime',
        'closed_date' => 'datetime',
        'known_error_date' => 'datetime',
        'resolution_target_date' => 'datetime',
        'actual_resolution_date' => 'datetime'
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($problem) {
            if (empty($problem->problem_number)) {
                $problem->problem_number = $problem->generateProblemNumber();
            }
            
            if (empty($problem->detected_date)) {
                $problem->detected_date = now();
            }
        });
    }

    /**
     * Generate unique problem number
     */
    public function generateProblemNumber(): string
    {
        $year = now()->format('Y');
        $lastProblem = static::where('problem_number', 'like', "PRB-{$year}-%")
                            ->orderBy('id', 'desc')
                            ->first();
        
        if ($lastProblem) {
            $lastNumber = intval(substr($lastProblem->problem_number, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }
        
        return "PRB-{$year}-{$newNumber}";
    }

    /**
     * Scope for open problems
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope for known errors
     */
    public function scopeKnownErrors(Builder $query): Builder
    {
        return $query->where('known_error', true);
    }

    /**
     * Scope for problems requiring attention
     */
    public function scopeRequiringAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('root_cause')
              ->orWhereNull('workaround')
              ->orWhere('status', 'investigating');
        });
    }

    /**
     * Get the reporter
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
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
     * Get related incidents
     */
    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'problem_incident')
                    ->withPivot('relationship_type', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get related changes
     */
    public function changes(): BelongsToMany
    {
        return $this->belongsToMany(Change::class, 'change_problem')
                    ->withTimestamps();
    }

    /**
     * Get related configuration items
     */
    public function configurationItems(): BelongsToMany
    {
        return $this->belongsToMany(ConfigurationItem::class, 'problem_configuration_item')
                    ->withPivot('impact_type', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get problem investigations
     */
    public function investigations(): HasMany
    {
        return $this->hasMany(ProblemInvestigation::class)
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get problem attachments
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get problem comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Mark as known error
     */
    public function markAsKnownError(string $workaround = null): void
    {
        $this->update([
            'known_error' => true,
            'known_error_date' => now(),
            'workaround' => $workaround ?? $this->workaround
        ]);
        
        // Create known error article if needed
        if ($workaround) {
            $this->createKnownErrorArticle();
        }
    }

    /**
     * Create known error article
     */
    protected function createKnownErrorArticle(): void
    {
        KnowledgeArticle::create([
            'title' => "Known Error: {$this->title}",
            'content' => $this->formatKnownErrorContent(),
            'category_id' => KnowledgeCategory::where('slug', 'known-errors')->first()?->id,
            'author_id' => auth()->id() ?? $this->assigned_to,
            'status' => 'published',
            'tags' => ['known-error', $this->category, "problem-{$this->id}"]
        ]);
    }

    /**
     * Format known error content
     */
    protected function formatKnownErrorContent(): string
    {
        return "## Problem Description\n\n{$this->description}\n\n"
             . "## Symptoms\n\n" . implode("\n- ", $this->symptoms ?? []) . "\n\n"
             . "## Root Cause\n\n{$this->root_cause}\n\n"
             . "## Workaround\n\n{$this->workaround}\n\n"
             . "## Permanent Solution\n\n" . ($this->permanent_solution ?? 'Under development') . "\n\n"
             . "## Related Problem\n\nProblem Number: {$this->problem_number}";
    }

    /**
     * Check if problem is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'resolved' || $this->status === 'closed') {
            return false;
        }
        
        return $this->resolution_target_date && $this->resolution_target_date->isPast();
    }

    /**
     * Get incident recurrence count
     */
    public function getIncidentRecurrenceCountAttribute(): int
    {
        return $this->incidents()->count();
    }

    /**
     * Get average time to resolve related incidents
     */
    public function getAverageIncidentResolutionTimeAttribute(): ?float
    {
        $avgMinutes = $this->incidents()
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');
            
        return $avgMinutes ? round($avgMinutes / 60, 2) : null;
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }
}