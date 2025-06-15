<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

abstract class BaseModel extends Model
{
    use HasUuids;
    
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (app()->has('current_tenant') && !$model->tenant_id) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });
    }
}