<?php

namespace App\Domains\CMDB\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CIRelationship extends BaseModel
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'ci_relationships';

    protected $fillable = [
        'source_ci_id',
        'target_ci_id',
        'relationship_type',
        'description',
        'impact_level',
        'is_active',
        'valid_from',
        'valid_to',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'is_active' => true,
        'impact_level' => 'medium',
        'metadata' => '{}'
    ];

    /**
     * Relationship type constants
     */
    const TYPE_DEPENDS_ON = 'depends_on';
    const TYPE_USED_BY = 'used_by';
    const TYPE_CONTAINS = 'contains';
    const TYPE_CONTAINED_IN = 'contained_in';
    const TYPE_CONNECTS_TO = 'connects_to';
    const TYPE_RUNS_ON = 'runs_on';
    const TYPE_HOSTS = 'hosts';
    const TYPE_MANAGES = 'manages';
    const TYPE_MANAGED_BY = 'managed_by';
    const TYPE_PROVIDES = 'provides';
    const TYPE_CONSUMES = 'consumes';
    const TYPE_BACKS_UP = 'backs_up';
    const TYPE_REPLICATED_TO = 'replicated_to';

    /**
     * Impact level constants
     */
    const IMPACT_LOW = 'low';
    const IMPACT_MEDIUM = 'medium';
    const IMPACT_HIGH = 'high';
    const IMPACT_CRITICAL = 'critical';

    /**
     * Get the source configuration item
     */
    public function sourceCI(): BelongsTo
    {
        return $this->belongsTo(ConfigurationItem::class, 'source_ci_id');
    }

    /**
     * Get the target configuration item
     */
    public function targetCI(): BelongsTo
    {
        return $this->belongsTo(ConfigurationItem::class, 'target_ci_id');
    }

    /**
     * Scope to get only active relationships
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get valid relationships (within validity period)
     */
    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_to')
              ->orWhere('valid_to', '>=', $now);
        });
    }

    /**
     * Scope to filter by relationship type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope to filter by impact level
     */
    public function scopeImpactLevel($query, $level)
    {
        return $query->where('impact_level', $level);
    }

    /**
     * Get available relationship types
     */
    public static function getRelationshipTypes(): array
    {
        return [
            self::TYPE_DEPENDS_ON => 'Depends On',
            self::TYPE_USED_BY => 'Used By',
            self::TYPE_CONTAINS => 'Contains',
            self::TYPE_CONTAINED_IN => 'Contained In',
            self::TYPE_CONNECTS_TO => 'Connects To',
            self::TYPE_RUNS_ON => 'Runs On',
            self::TYPE_HOSTS => 'Hosts',
            self::TYPE_MANAGES => 'Manages',
            self::TYPE_MANAGED_BY => 'Managed By',
            self::TYPE_PROVIDES => 'Provides',
            self::TYPE_CONSUMES => 'Consumes',
            self::TYPE_BACKS_UP => 'Backs Up',
            self::TYPE_REPLICATED_TO => 'Replicated To'
        ];
    }

    /**
     * Get available impact levels
     */
    public static function getImpactLevels(): array
    {
        return [
            self::IMPACT_LOW => 'Low',
            self::IMPACT_MEDIUM => 'Medium',
            self::IMPACT_HIGH => 'High',
            self::IMPACT_CRITICAL => 'Critical'
        ];
    }

    /**
     * Get the inverse relationship type
     */
    public function getInverseType(): ?string
    {
        $inverseMap = [
            self::TYPE_DEPENDS_ON => self::TYPE_USED_BY,
            self::TYPE_USED_BY => self::TYPE_DEPENDS_ON,
            self::TYPE_CONTAINS => self::TYPE_CONTAINED_IN,
            self::TYPE_CONTAINED_IN => self::TYPE_CONTAINS,
            self::TYPE_MANAGES => self::TYPE_MANAGED_BY,
            self::TYPE_MANAGED_BY => self::TYPE_MANAGES,
            self::TYPE_PROVIDES => self::TYPE_CONSUMES,
            self::TYPE_CONSUMES => self::TYPE_PROVIDES,
        ];

        return $inverseMap[$this->relationship_type] ?? null;
    }

    /**
     * Check if relationship is bidirectional
     */
    public function isBidirectional(): bool
    {
        $bidirectionalTypes = [
            self::TYPE_CONNECTS_TO,
            self::TYPE_REPLICATED_TO
        ];

        return in_array($this->relationship_type, $bidirectionalTypes);
    }

    /**
     * Check if relationship is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }

        if ($this->valid_to && $this->valid_to < $now) {
            return false;
        }

        return true;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent self-referencing relationships
        static::saving(function ($relationship) {
            if ($relationship->source_ci_id === $relationship->target_ci_id) {
                throw new \InvalidArgumentException('A CI cannot have a relationship with itself.');
            }
        });
    }
}