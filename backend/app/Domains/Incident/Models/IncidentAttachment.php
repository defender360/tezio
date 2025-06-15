<?php

namespace App\Domains\Incident\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAttachment extends BaseModel
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
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the incident that owns the attachment.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}