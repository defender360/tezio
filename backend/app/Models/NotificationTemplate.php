<?php

namespace App\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasUuid;

class NotificationTemplate extends BaseModel
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'category',
        'channels',
        'email_subject',
        'email_body_html',
        'email_body_text',
        'sms_body',
        'in_app_title',
        'in_app_body',
        'webhook_payload',
        'available_variables',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'channels' => 'array',
        'webhook_payload' => 'array',
        'available_variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    const CATEGORY_INCIDENT = 'incident';
    const CATEGORY_CHANGE = 'change';
    const CATEGORY_PROBLEM = 'problem';
    const CATEGORY_SERVICE_REQUEST = 'service_request';
    const CATEGORY_KNOWLEDGE = 'knowledge';
    const CATEGORY_SLA = 'sla';
    const CATEGORY_SYSTEM = 'system';

    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';
    const CHANNEL_WEBHOOK = 'webhook';
    const CHANNEL_SLACK = 'slack';
    const CHANNEL_TEAMS = 'teams';

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_INCIDENT,
            self::CATEGORY_CHANGE,
            self::CATEGORY_PROBLEM,
            self::CATEGORY_SERVICE_REQUEST,
            self::CATEGORY_KNOWLEDGE,
            self::CATEGORY_SLA,
            self::CATEGORY_SYSTEM,
        ];
    }

    /**
     * Get all available channels.
     */
    public static function getChannels(): array
    {
        return [
            self::CHANNEL_EMAIL,
            self::CHANNEL_SMS,
            self::CHANNEL_IN_APP,
            self::CHANNEL_WEBHOOK,
            self::CHANNEL_SLACK,
            self::CHANNEL_TEAMS,
        ];
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for templates by channel.
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }

    /**
     * Check if template supports a specific channel.
     */
    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? []);
    }

    /**
     * Render template with variables.
     */
    public function render(string $content, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
                $content = str_replace('{{ ' . $key . ' }}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * Render email subject.
     */
    public function renderEmailSubject(array $variables = []): ?string
    {
        return $this->email_subject ? $this->render($this->email_subject, $variables) : null;
    }

    /**
     * Render email body HTML.
     */
    public function renderEmailBodyHtml(array $variables = []): ?string
    {
        return $this->email_body_html ? $this->render($this->email_body_html, $variables) : null;
    }

    /**
     * Render email body text.
     */
    public function renderEmailBodyText(array $variables = []): ?string
    {
        return $this->email_body_text ? $this->render($this->email_body_text, $variables) : null;
    }

    /**
     * Render SMS body.
     */
    public function renderSmsBody(array $variables = []): ?string
    {
        return $this->sms_body ? $this->render($this->sms_body, $variables) : null;
    }

    /**
     * Render in-app notification.
     */
    public function renderInApp(array $variables = []): array
    {
        return [
            'title' => $this->in_app_title ? $this->render($this->in_app_title, $variables) : null,
            'body' => $this->in_app_body ? $this->render($this->in_app_body, $variables) : null,
        ];
    }

    /**
     * Get webhook payload with variables.
     */
    public function getWebhookPayload(array $variables = []): array
    {
        $payload = $this->webhook_payload ?? [];
        
        array_walk_recursive($payload, function (&$value) use ($variables) {
            if (is_string($value)) {
                $value = $this->render($value, $variables);
            }
        });

        return $payload;
    }
}