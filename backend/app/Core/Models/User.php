<?php

namespace App\Core\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    MustVerifyEmailContract
{
    use Authenticatable,
        Authorizable,
        CanResetPassword,
        MustVerifyEmail,
        HasApiTokens,
        Notifiable,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'email',
        'username',
        'email_verified_at',
        'password',
        'first_name',
        'last_name',
        'display_name',
        'phone',
        'mobile',
        'avatar_url',
        'birth_date',
        'gender',
        'employee_id',
        'job_title',
        'bio',
        'skills',
        'certifications',
        'timezone',
        'locale',
        'country',
        'city',
        'address',
        'auth_provider',
        'auth_provider_id',
        'mfa_enabled',
        'mfa_secret',
        'mfa_recovery_codes',
        'password_changed_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
        'preferences',
        'notification_settings',
        'email_notifications',
        'push_notifications',
        'sms_notifications',
        'is_super_admin',
        'status',
        'activation_token',
        'activated_at',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
        'activation_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'activated_at' => 'datetime',
        'birth_date' => 'date',
        'skills' => 'array',
        'certifications' => 'array',
        'mfa_recovery_codes' => 'encrypted:array',
        'preferences' => 'array',
        'notification_settings' => 'array',
        'metadata' => 'array',
        'mfa_enabled' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'is_super_admin' => 'boolean',
        'failed_login_attempts' => 'integer',
    ];

    /**
     * Get the tenants that the user belongs to.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot([
                'role',
                'is_owner',
                'is_active',
                'permissions',
                'restrictions',
                'department',
                'team',
                'job_title',
                'last_login_at',
                'last_login_ip',
                'login_count',
                'invited_at',
                'invited_by',
                'joined_at',
                'deactivated_at',
                'deactivated_by',
                'deactivation_reason',
            ])
            ->withTimestamps();
    }

    /**
     * Get the user's current tenant.
     */
    public function currentTenant(): ?Tenant
    {
        if ($tenantId = session('current_tenant_id')) {
            return $this->tenants()->where('tenants.id', $tenantId)->first();
        }

        return $this->tenants()->where('is_active', true)->first();
    }

    /**
     * Check if user has access to a specific tenant.
     */
    public function belongsToTenant($tenantId): bool
    {
        return $this->tenants()
            ->where('tenants.id', $tenantId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's role in a specific tenant.
     */
    public function getRoleInTenant($tenantId): ?string
    {
        $tenant = $this->tenants()->where('tenants.id', $tenantId)->first();
        
        return $tenant ? $tenant->pivot->role : null;
    }

    /**
     * Check if user is owner of a specific tenant.
     */
    public function isOwnerOfTenant($tenantId): bool
    {
        $tenant = $this->tenants()->where('tenants.id', $tenantId)->first();
        
        return $tenant ? $tenant->pivot->is_owner : false;
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the user's display name.
     */
    public function getNameAttribute(): string
    {
        return $this->display_name ?: $this->full_name;
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->locked_until;
    }

    /**
     * Check if the user account is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->update(['locked_until' => now()->addMinutes(30)]);
        }
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
        
        $this->resetFailedLoginAttempts();
    }
}