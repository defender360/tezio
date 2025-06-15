<?php

namespace App\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends BaseModel
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'related_type',
        'related_id',
        'priority',
        'status',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model.
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if ($this->status === self::STATUS_UNREAD) {
            $this->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'status' => self::STATUS_UNREAD,
            'read_at' => null,
        ]);
    }

    /**
     * Archive the notification.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }

    /**
     * Scope for notifications by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for recent notifications.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get icon for notification type.
     */
    public function getIcon(): string
    {
        $icons = [
            'incident_created' => 'alert-circle',
            'incident_updated' => 'refresh-circle',
            'incident_resolved' => 'check-circle',
            'sla_breach' => 'alert-triangle',
            'sla_warning' => 'clock',
            'change_requested' => 'git-pull-request',
            'change_approved' => 'check-square',
            'change_rejected' => 'x-square',
            'change_completed' => 'check-circle',
            'problem_created' => 'help-circle',
            'problem_resolved' => 'check-circle',
            'knowledge_article_published' => 'book-open',
            'service_request_created' => 'inbox',
            'service_request_completed' => 'check-circle',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get color for priority.
     */
    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_LOW => 'blue',
            default => 'gray',
        };
    }
}