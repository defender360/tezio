<?php

namespace App\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends BaseModel
{
    use HasAuditLog;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'subdomain',
        'config',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the incidents for the tenant.
     */
    public function incidents()
    {
        return $this->hasMany(\App\Domains\Incident\Models\Incident::class);
    }
}