<?php

namespace App\Core\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for a model.
     */
    protected static function bootBelongsToTenant()
    {
        // Add global scope
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant')) {
                $builder->where('tenant_id', app('current_tenant')->id);
            }
        });
        
        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (app()->has('current_tenant') && !$model->tenant_id) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });
    }
    
    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}