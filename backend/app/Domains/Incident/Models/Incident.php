<?php

namespace App\Domains\Incident\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\IncidentFactory;

class Incident extends BaseModel
{
    use BelongsToTenant;
    use HasAuditLog;
    use HasFactory;

    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    const IMPACT_LOW = 'low';
    const IMPACT_MEDIUM = 'medium';
    const IMPACT_HIGH = 'high';
    const IMPACT_ENTERPRISE = 'enterprise';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'priority',
        'impact',
        'urgency',
        'category',
        'subcategory',
        'assigned_to',
        'assigned_group',
        'created_by',
        'resolved_at',
        'closed_at',
        'sla_deadline',
        'breach_time',
        'resolution_notes',
        'customer_notes',
        'tags',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'breach_time' => 'datetime',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    /**
     * Get the user who created the incident.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to the incident.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the comments for the incident.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    /**
     * Get the attachments for the incident.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }

    /**
     * Get the history for the incident.
     */
    public function history(): HasMany
    {
        return $this->hasMany(IncidentHistory::class);
    }

    /**
     * Check if the incident is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->sla_deadline && $this->sla_deadline->isPast() && !$this->isResolved();
    }

    /**
     * Check if the incident is resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Get the SLA hours based on priority.
     */
    public function getSlaHours(): int
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 4,
            self::PRIORITY_HIGH => 8,
            self::PRIORITY_MEDIUM => 24,
            self::PRIORITY_LOW => 72,
            default => 72,
        };
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return IncidentFactory::new();
    }
}