<?php

namespace App\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class NotificationPreference extends BaseModel
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'notification_type',
        'channels',
        'enabled',
        'frequency',
        'schedule',
        'timezone',
        'filters',
        'enable_quiet_hours',
        'quiet_hours_start',
        'quiet_hours_end',
        'quiet_hours_days',
    ];

    protected $casts = [
        'channels' => 'array',
        'enabled' => 'boolean',
        'schedule' => 'array',
        'filters' => 'array',
        'enable_quiet_hours' => 'boolean',
        'quiet_hours_days' => 'array',
    ];

    const FREQUENCY_IMMEDIATE = 'immediate';
    const FREQUENCY_HOURLY = 'hourly';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available frequencies.
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_IMMEDIATE,
            self::FREQUENCY_HOURLY,
            self::FREQUENCY_DAILY,
            self::FREQUENCY_WEEKLY,
        ];
    }

    /**
     * Get default preferences for a notification type.
     */
    public static function getDefaultPreferences(string $notificationType): array
    {
        $defaults = [
            'incident_created' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
                'filters' => ['priority' => ['high', 'critical']],
            ],
            'incident_updated' => [
                'channels' => ['in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
                'filters' => ['priority' => ['high', 'critical']],
            ],
            'incident_resolved' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
            ],
            'sla_breach' => [
                'channels' => ['email', 'sms', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
            ],
            'sla_warning' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
            ],
            'change_requested' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
            ],
            'change_approved' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_IMMEDIATE,
            ],
            'problem_created' => [
                'channels' => ['email', 'in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_DAILY,
            ],
            'knowledge_article_published' => [
                'channels' => ['in_app'],
                'enabled' => true,
                'frequency' => self::FREQUENCY_WEEKLY,
            ],
        ];

        return $defaults[$notificationType] ?? [
            'channels' => ['in_app'],
            'enabled' => true,
            'frequency' => self::FREQUENCY_IMMEDIATE,
        ];
    }

    /**
     * Check if notification should be sent based on preferences.
     */
    public function shouldSendNotification(array $notificationData = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check filters
        if (!$this->passesFilters($notificationData)) {
            return false;
        }

        // Check quiet hours
        if ($this->isInQuietHours()) {
            return false;
        }

        // Check frequency
        if (!$this->shouldSendBasedOnFrequency()) {
            return false;
        }

        return true;
    }

    /**
     * Check if notification passes filters.
     */
    protected function passesFilters(array $notificationData): bool
    {
        if (empty($this->filters)) {
            return true;
        }

        foreach ($this->filters as $field => $allowedValues) {
            if (isset($notificationData[$field])) {
                if (is_array($allowedValues) && !in_array($notificationData[$field], $allowedValues)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if currently in quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (!$this->enable_quiet_hours) {
            return false;
        }

        $now = Carbon::now($this->timezone ?? 'UTC');
        $dayOfWeek = strtolower($now->format('D'));

        // Check if today is in quiet hours days
        if ($this->quiet_hours_days && !in_array($dayOfWeek, $this->quiet_hours_days)) {
            return false;
        }

        // Check time
        if ($this->quiet_hours_start && $this->quiet_hours_end) {
            $start = Carbon::parse($this->quiet_hours_start, $this->timezone);
            $end = Carbon::parse($this->quiet_hours_end, $this->timezone);

            // Handle overnight quiet hours
            if ($end->lt($start)) {
                return $now->gte($start) || $now->lte($end);
            }

            return $now->between($start, $end);
        }

        return false;
    }

    /**
     * Check if should send based on frequency.
     */
    protected function shouldSendBasedOnFrequency(): bool
    {
        if ($this->frequency === self::FREQUENCY_IMMEDIATE) {
            return true;
        }

        // For non-immediate frequencies, check schedule
        if ($this->schedule) {
            $now = Carbon::now($this->timezone ?? 'UTC');

            switch ($this->frequency) {
                case self::FREQUENCY_HOURLY:
                    return $now->minute === ($this->schedule['minute'] ?? 0);

                case self::FREQUENCY_DAILY:
                    return $now->hour === ($this->schedule['hour'] ?? 9) && 
                           $now->minute === ($this->schedule['minute'] ?? 0);

                case self::FREQUENCY_WEEKLY:
                    return $now->dayOfWeek === ($this->schedule['day_of_week'] ?? 1) &&
                           $now->hour === ($this->schedule['hour'] ?? 9) &&
                           $now->minute === ($this->schedule['minute'] ?? 0);
            }
        }

        return true;
    }

    /**
     * Get enabled channels for a specific delivery method.
     */
    public function getEnabledChannels(): array
    {
        return $this->channels ?? [];
    }

    /**
     * Update user preferences.
     */
    public static function updateUserPreferences(User $user, string $notificationType, array $data): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
            ],
            array_merge($data, ['tenant_id' => $user->tenant_id])
        );
    }
}