<?php

namespace App\Domains\Incident\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentComment extends BaseModel
{
    use BelongsToTenant;
    use HasAuditLog;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'incident_id',
        'user_id',
        'comment',
        'is_internal',
        'mentioned_users',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_internal' => 'boolean',
        'mentioned_users' => 'array',
    ];

    /**
     * Get the incident that owns the comment.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the user who created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}