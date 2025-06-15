<?php

namespace App\Domains\CMDB\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigurationItem extends BaseModel
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'configuration_items';

    protected $fillable = [
        'ci_type_id',
        'name',
        'code',
        'description',
        'status',
        'owner_id',
        'location',
        'criticality',
        'environment',
        'attributes',
        'metadata',
        'is_active',
        'discovered_at',
        'verified_at',
        'decommissioned_at'
    ];

    protected $casts = [
        'attributes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'discovered_at' => 'datetime',
        'verified_at' => 'datetime',
        'decommissioned_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'operational',
        'criticality' => 'medium',
        'is_active' => true,
        'attributes' => '{}',
        'metadata' => '{}'
    ];

    /**
     * Status constants
     */
    const STATUS_PLANNED = 'planned';
    const STATUS_BUILDING = 'building';
    const STATUS_OPERATIONAL = 'operational';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DECOMMISSIONED = 'decommissioned';

    /**
     * Criticality constants
     */
    const CRITICALITY_LOW = 'low';
    const CRITICALITY_MEDIUM = 'medium';
    const CRITICALITY_HIGH = 'high';
    const CRITICALITY_CRITICAL = 'critical';

    /**
     * Environment constants
     */
    const ENV_DEVELOPMENT = 'development';
    const ENV_TESTING = 'testing';
    const ENV_STAGING = 'staging';
    const ENV_PRODUCTION = 'production';

    /**
     * Get the CI type
     */
    public function ciType(): BelongsTo
    {
        return $this->belongsTo(CIType::class, 'ci_type_id');
    }

    /**
     * Get the owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all relationships where this CI is the source
     */
    public function sourceRelationships(): HasMany
    {
        return $this->hasMany(CIRelationship::class, 'source_ci_id');
    }

    /**
     * Get all relationships where this CI is the target
     */
    public function targetRelationships(): HasMany
    {
        return $this->hasMany(CIRelationship::class, 'target_ci_id');
    }

    /**
     * Get all related CIs (both as source and target)
     */
    public function relatedItems(): BelongsToMany
    {
        return $this->belongsToMany(
            ConfigurationItem::class,
            'ci_relationships',
            'source_ci_id',
            'target_ci_id'
        )->withPivot(['relationship_type', 'description', 'is_active'])
          ->withTimestamps();
    }

    /**
     * Get all attribute values
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(CIAttribute::class, 'configuration_item_id');
    }

    /**
     * Scope to get only active CIs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by criticality
     */
    public function scopeCriticality($query, $criticality)
    {
        return $query->where('criticality', $criticality);
    }

    /**
     * Scope to filter by environment
     */
    public function scopeEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope to filter by CI type
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('ci_type_id', $typeId);
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_BUILDING => 'Building',
            self::STATUS_OPERATIONAL => 'Operational',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_DECOMMISSIONED => 'Decommissioned'
        ];
    }

    /**
     * Get available criticality levels
     */
    public static function getCriticalityLevels(): array
    {
        return [
            self::CRITICALITY_LOW => 'Low',
            self::CRITICALITY_MEDIUM => 'Medium',
            self::CRITICALITY_HIGH => 'High',
            self::CRITICALITY_CRITICAL => 'Critical'
        ];
    }

    /**
     * Get available environments
     */
    public static function getEnvironments(): array
    {
        return [
            self::ENV_DEVELOPMENT => 'Development',
            self::ENV_TESTING => 'Testing',
            self::ENV_STAGING => 'Staging',
            self::ENV_PRODUCTION => 'Production'
        ];
    }

    /**
     * Check if CI is operational
     */
    public function isOperational(): bool
    {
        return $this->status === self::STATUS_OPERATIONAL && $this->is_active;
    }

    /**
     * Get custom attribute value by key
     */
    public function getCustomAttribute($key)
    {
        $attributes = $this->attributes ?? [];
        return $attributes[$key] ?? null;
    }

    /**
     * Set custom attribute value
     */
    public function setCustomAttribute($key, $value)
    {
        $attributes = $this->attributes ?? [];
        $attributes[$key] = $value;
        $this->attributes = $attributes;

        return $this;
    }
}