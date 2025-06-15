<?php

namespace App\Domains\Tenant\Models;

use App\Core\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Tenant extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'company_name',
        'company_email',
        'company_phone',
        'company_website',
        'company_address',
        'tax_id',
        'plan',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'max_users',
        'max_tickets_per_month',
        'ai_features_enabled',
        'settings',
        'features',
        'database',
        'timezone',
        'locale',
        'currency',
        'mfa_required',
        'password_expiry_days',
        'allowed_ip_addresses',
        'sso_enabled',
        'sso_config',
        'logo_url',
        'favicon_url',
        'brand_colors',
        'custom_css',
        'api_key',
        'webhook_secret',
        'integrations',
        'created_by',
        'onboarding_status',
        'onboarded_at',
        'last_activity_at',
        'storage_used_bytes',
        'storage_limit_bytes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'onboarded_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'settings' => 'array',
        'features' => 'array',
        'allowed_ip_addresses' => 'array',
        'sso_config' => 'encrypted:array',
        'brand_colors' => 'array',
        'integrations' => 'encrypted:array',
        'ai_features_enabled' => 'boolean',
        'mfa_required' => 'boolean',
        'sso_enabled' => 'boolean',
        'max_users' => 'integer',
        'max_tickets_per_month' => 'integer',
        'password_expiry_days' => 'integer',
        'storage_used_bytes' => 'integer',
        'storage_limit_bytes' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'api_key',
        'webhook_secret',
        'sso_config',
        'integrations',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
            
            if (empty($tenant->subdomain)) {
                $tenant->subdomain = $tenant->slug;
            }
            
            if (empty($tenant->api_key)) {
                $tenant->api_key = 'sk_' . Str::random(32);
            }
            
            if (empty($tenant->webhook_secret)) {
                $tenant->webhook_secret = 'whsec_' . Str::random(32);
            }
            
            if (empty($tenant->trial_ends_at)) {
                $tenant->trial_ends_at = Carbon::now()->addDays(14);
            }
        });
    }

    /**
     * Get the users that belong to the tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
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
     * Get the domains for the tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * Get the primary domain for the tenant.
     */
    public function primaryDomain()
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    /**
     * Check if the tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the tenant is in trial.
     */
    public function isInTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant subscription is valid.
     */
    public function hasValidSubscription(): bool
    {
        return in_array($this->status, ['active', 'trial']) && 
               (!$this->subscription_ends_at || $this->subscription_ends_at->isFuture());
    }

    /**
     * Check if the tenant can add more users.
     */
    public function canAddMoreUsers(): bool
    {
        return $this->users()->where('is_active', true)->count() < $this->max_users;
    }

    /**
     * Check if the tenant has reached ticket limit for the month.
     */
    public function hasReachedTicketLimit(): bool
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $ticketCount = $this->incidents()
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
            ->count();
            
        return $ticketCount >= $this->max_tickets_per_month;
    }

    /**
     * Get storage usage percentage.
     */
    public function getStorageUsagePercentage(): float
    {
        if ($this->storage_limit_bytes == 0) {
            return 0;
        }
        
        return round(($this->storage_used_bytes / $this->storage_limit_bytes) * 100, 2);
    }

    /**
     * Update last activity timestamp.
     */
    public function touchLastActivity(): void
    {
        $this->update(['last_activity_at' => Carbon::now()]);
    }

    /**
     * Get feature value.
     */
    public function getFeature(string $key, $default = null)
    {
        return data_get($this->features, $key, $default);
    }

    /**
     * Get setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Check if a feature is enabled.
     */
    public function hasFeature(string $feature): bool
    {
        return (bool) $this->getFeature($feature, false);
    }

    /**
     * Get the full domain URL.
     */
    public function getUrl(): string
    {
        if ($this->domain) {
            return 'https://' . $this->domain;
        }
        
        return 'https://' . $this->subdomain . '.' . config('app.central_domain');
    }
}