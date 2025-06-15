<?php

namespace App\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;

class NotificationChannel extends BaseModel
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'description',
        'configuration',
        'is_active',
        'is_default',
        'rate_limit',
        'daily_limit',
        'messages_sent_today',
        'last_sent_at',
        'rate_limit_reset_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_sent_at' => 'datetime',
        'rate_limit_reset_at' => 'datetime',
    ];

    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_SLACK = 'slack';
    const TYPE_TEAMS = 'teams';
    const TYPE_WEBHOOK = 'webhook';

    /**
     * Get all available channel types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_EMAIL,
            self::TYPE_SMS,
            self::TYPE_SLACK,
            self::TYPE_TEAMS,
            self::TYPE_WEBHOOK,
        ];
    }

    /**
     * Get configuration schema for channel type.
     */
    public static function getConfigurationSchema(string $type): array
    {
        return match ($type) {
            self::TYPE_EMAIL => [
                'smtp_host' => 'required|string',
                'smtp_port' => 'required|integer',
                'smtp_username' => 'required|string',
                'smtp_password' => 'required|string',
                'smtp_encryption' => 'required|in:tls,ssl,none',
                'from_email' => 'required|email',
                'from_name' => 'required|string',
            ],
            self::TYPE_SMS => [
                'provider' => 'required|in:twilio,nexmo,aws_sns',
                'api_key' => 'required|string',
                'api_secret' => 'required|string',
                'from_number' => 'required|string',
            ],
            self::TYPE_SLACK => [
                'webhook_url' => 'required|url',
                'channel' => 'nullable|string',
                'username' => 'nullable|string',
                'icon_emoji' => 'nullable|string',
            ],
            self::TYPE_TEAMS => [
                'webhook_url' => 'required|url',
            ],
            self::TYPE_WEBHOOK => [
                'url' => 'required|url',
                'method' => 'required|in:GET,POST,PUT,PATCH',
                'headers' => 'nullable|array',
                'auth_type' => 'nullable|in:none,basic,bearer,api_key',
                'auth_credentials' => 'nullable|array',
            ],
            default => [],
        };
    }

    /**
     * Scope for active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default channels.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for channels by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if rate limit is exceeded.
     */
    public function isRateLimitExceeded(): bool
    {
        if (!$this->rate_limit) {
            return false;
        }

        // Reset counter if needed
        if ($this->rate_limit_reset_at && $this->rate_limit_reset_at->isPast()) {
            $this->resetRateLimit();
        }

        return $this->messages_sent_today >= $this->rate_limit;
    }

    /**
     * Check if daily limit is exceeded.
     */
    public function isDailyLimitExceeded(): bool
    {
        if (!$this->daily_limit) {
            return false;
        }

        // Reset counter if it's a new day
        if ($this->last_sent_at && $this->last_sent_at->isYesterday()) {
            $this->messages_sent_today = 0;
            $this->save();
        }

        return $this->messages_sent_today >= $this->daily_limit;
    }

    /**
     * Increment message counter.
     */
    public function incrementMessageCounter(): void
    {
        $this->increment('messages_sent_today');
        $this->update([
            'last_sent_at' => now(),
            'rate_limit_reset_at' => now()->addMinute(),
        ]);
    }

    /**
     * Reset rate limit counter.
     */
    public function resetRateLimit(): void
    {
        $this->update([
            'messages_sent_today' => 0,
            'rate_limit_reset_at' => now()->addMinute(),
        ]);
    }

    /**
     * Get decrypted configuration value.
     */
    public function getConfigValue(string $key, $default = null)
    {
        $value = data_get($this->configuration, $key, $default);

        // Decrypt sensitive fields
        if (in_array($key, ['smtp_password', 'api_key', 'api_secret', 'auth_credentials']) && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Set encrypted configuration value.
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->configuration ?? [];

        // Encrypt sensitive fields
        if (in_array($key, ['smtp_password', 'api_key', 'api_secret', 'auth_credentials']) && $value) {
            $value = Crypt::encryptString($value);
        }

        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Test channel connection.
     */
    public function testConnection(): array
    {
        try {
            switch ($this->type) {
                case self::TYPE_EMAIL:
                    // Test SMTP connection
                    $transport = (new \Swift_SmtpTransport(
                        $this->getConfigValue('smtp_host'),
                        $this->getConfigValue('smtp_port')
                    ))
                        ->setUsername($this->getConfigValue('smtp_username'))
                        ->setPassword($this->getConfigValue('smtp_password'))
                        ->setEncryption($this->getConfigValue('smtp_encryption'));

                    $mailer = new \Swift_Mailer($transport);
                    $mailer->getTransport()->start();

                    return ['success' => true, 'message' => 'SMTP connection successful'];

                case self::TYPE_WEBHOOK:
                    // Test webhook URL
                    $response = \Http::timeout(5)->get($this->getConfigValue('url'));
                    
                    return [
                        'success' => $response->successful(),
                        'message' => $response->successful() ? 'Webhook URL is reachable' : 'Failed to reach webhook URL',
                        'status_code' => $response->status(),
                    ];

                default:
                    return ['success' => true, 'message' => 'Channel configuration appears valid'];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }
}