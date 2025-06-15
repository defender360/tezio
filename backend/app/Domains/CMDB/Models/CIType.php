<?php

namespace App\Domains\CMDB\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CIType extends BaseModel
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'ci_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'parent_id',
        'schema',
        'is_active',
        'order_index'
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
        'order_index' => 'integer'
    ];

    protected $attributes = [
        'is_active' => true,
        'order_index' => 0
    ];

    /**
     * Get the parent CI type
     */
    public function parent()
    {
        return $this->belongsTo(CIType::class, 'parent_id');
    }

    /**
     * Get the child CI types
     */
    public function children(): HasMany
    {
        return $this->hasMany(CIType::class, 'parent_id');
    }

    /**
     * Get all configuration items of this type
     */
    public function configurationItems(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class, 'ci_type_id');
    }

    /**
     * Get all attributes defined for this CI type
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(CIAttribute::class, 'ci_type_id');
    }

    /**
     * Scope to get only active CI types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root CI types (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full hierarchical path for this CI type
     */
    public function getPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}