<?php

namespace App\Domains\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TenantDomain extends Model
{
    use HasFactory;
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
        'is_verified',
        'verification_token',
        'verified_at',
        'ssl_status',
        'ssl_expires_at',
        'dns_records',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'dns_records' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TenantDomain $domain) {
            if (empty($domain->verification_token)) {
                $domain->verification_token = Str::random(32);
            }
        });
    }

    /**
     * Get the tenant that owns the domain.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Mark the domain as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_token' => null,
        ]);
    }

    /**
     * Mark the domain as primary.
     */
    public function markAsPrimary(): void
    {
        // Remove primary flag from other domains
        $this->tenant->domains()->update(['is_primary' => false]);
        
        // Set this domain as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Get the verification DNS record.
     */
    public function getVerificationDnsRecord(): array
    {
        return [
            'type' => 'TXT',
            'name' => '_defender360-verification',
            'value' => $this->verification_token,
        ];
    }

    /**
     * Check if SSL certificate is valid.
     */
    public function hasSslCertificate(): bool
    {
        return $this->ssl_status === 'active' && 
               $this->ssl_expires_at && 
               $this->ssl_expires_at->isFuture();
    }
}