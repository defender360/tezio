<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\IncidentNotification;
use App\Mail\ChangeNotification;
use App\Mail\SlaBreachNotification;

class ChannelService
{
    /**
     * Send notification through specified channel.
     */
    public function send(
        string $channelType,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $channel = $this->getActiveChannel($channelType, $notification->tenant_id);

        if (!$channel) {
            Log::warning("No active channel found for type: {$channelType}");
            return false;
        }

        // Check rate limits
        if ($channel->isRateLimitExceeded() || $channel->isDailyLimitExceeded()) {
            Log::warning("Rate limit exceeded for channel: {$channel->name}");
            return false;
        }

        try {
            $result = match ($channelType) {
                NotificationChannel::TYPE_EMAIL => $this->sendEmail($channel, $notification, $template, $variables),
                NotificationChannel::TYPE_SMS => $this->sendSms($channel, $notification, $template, $variables),
                NotificationChannel::TYPE_SLACK => $this->sendSlack($channel, $notification, $template, $variables),
                NotificationChannel::TYPE_TEAMS => $this->sendTeams($channel, $notification, $template, $variables),
                NotificationChannel::TYPE_WEBHOOK => $this->sendWebhook($channel, $notification, $template, $variables),
                default => false,
            };

            if ($result) {
                $channel->incrementMessageCounter();
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to send notification through {$channelType}: {$e->getMessage()}", [
                'notification_id' => $notification->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get active channel by type and tenant.
     */
    protected function getActiveChannel(string $type, string $tenantId): ?NotificationChannel
    {
        return NotificationChannel::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->first();
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(
        NotificationChannel $channel,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $mailableClass = $this->getMailableClass($notification->type);

        if (!$mailableClass) {
            // Fallback to generic email
            $subject = $template->renderEmailSubject($variables);
            $htmlBody = $template->renderEmailBodyHtml($variables);
            $textBody = $template->renderEmailBodyText($variables);

            Mail::raw($textBody, function ($message) use ($notification, $subject, $htmlBody) {
                $message->to($notification->user->email)
                    ->subject($subject);
                
                if ($htmlBody) {
                    $message->html($htmlBody);
                }
            });
        } else {
            Mail::to($notification->user->email)
                ->send(new $mailableClass($notification, $variables));
        }

        return true;
    }

    /**
     * Get mailable class for notification type.
     */
    protected function getMailableClass(string $type): ?string
    {
        $mailables = [
            'incident_created' => IncidentNotification::class,
            'incident_updated' => IncidentNotification::class,
            'incident_resolved' => IncidentNotification::class,
            'sla_breach' => SlaBreachNotification::class,
            'sla_warning' => SlaBreachNotification::class,
            'change_requested' => ChangeNotification::class,
            'change_approved' => ChangeNotification::class,
            'change_rejected' => ChangeNotification::class,
        ];

        return $mailables[$type] ?? null;
    }

    /**
     * Send SMS notification.
     */
    protected function sendSms(
        NotificationChannel $channel,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $provider = $channel->getConfigValue('provider');
        $body = $template->renderSmsBody($variables);

        if (!$body) {
            return false;
        }

        return match ($provider) {
            'twilio' => $this->sendTwilioSms($channel, $notification->user->phone, $body),
            'nexmo' => $this->sendNexmoSms($channel, $notification->user->phone, $body),
            'aws_sns' => $this->sendAwsSns($channel, $notification->user->phone, $body),
            default => false,
        };
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendTwilioSms(NotificationChannel $channel, string $to, string $body): bool
    {
        $accountSid = $channel->getConfigValue('api_key');
        $authToken = $channel->getConfigValue('api_secret');
        $from = $channel->getConfigValue('from_number');

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $body,
            ]);

        return $response->successful();
    }

    /**
     * Send SMS via Nexmo (Vonage).
     */
    protected function sendNexmoSms(NotificationChannel $channel, string $to, string $body): bool
    {
        $apiKey = $channel->getConfigValue('api_key');
        $apiSecret = $channel->getConfigValue('api_secret');
        $from = $channel->getConfigValue('from_number');

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $from,
            'to' => $to,
            'text' => $body,
        ]);

        return $response->successful() && $response->json('messages.0.status') === '0';
    }

    /**
     * Send SMS via AWS SNS.
     */
    protected function sendAwsSns(NotificationChannel $channel, string $to, string $body): bool
    {
        // This would require AWS SDK implementation
        // For now, returning false as placeholder
        return false;
    }

    /**
     * Send Slack notification.
     */
    protected function sendSlack(
        NotificationChannel $channel,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        $channelName = $channel->getConfigValue('channel');
        $username = $channel->getConfigValue('username', 'Tezio Notifications');
        $iconEmoji = $channel->getConfigValue('icon_emoji', ':bell:');

        $inApp = $template->renderInApp($variables);

        $payload = [
            'channel' => $channelName,
            'username' => $username,
            'icon_emoji' => $iconEmoji,
            'attachments' => [
                [
                    'color' => $this->getSlackColor($notification->priority),
                    'title' => $inApp['title'],
                    'text' => $inApp['body'],
                    'fields' => [
                        [
                            'title' => 'Type',
                            'value' => ucfirst(str_replace('_', ' ', $notification->type)),
                            'short' => true,
                        ],
                        [
                            'title' => 'Priority',
                            'value' => ucfirst($notification->priority),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'Tezio ITSM',
                    'ts' => now()->timestamp,
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'View Details',
                            'url' => $variables['app_url'] . '/notifications/' . $notification->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::post($webhookUrl, $payload);

        return $response->successful();
    }

    /**
     * Get Slack color based on priority.
     */
    protected function getSlackColor(string $priority): string
    {
        return match ($priority) {
            Notification::PRIORITY_CRITICAL => '#721c24',
            Notification::PRIORITY_HIGH => '#dc3545',
            Notification::PRIORITY_MEDIUM => '#ffc107',
            Notification::PRIORITY_LOW => '#007bff',
            default => '#6c757d',
        };
    }

    /**
     * Send Microsoft Teams notification.
     */
    protected function sendTeams(
        NotificationChannel $channel,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        $inApp = $template->renderInApp($variables);

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => $this->getTeamsColor($notification->priority),
            'summary' => $inApp['title'],
            'sections' => [
                [
                    'activityTitle' => $inApp['title'],
                    'activitySubtitle' => 'Tezio ITSM Notification',
                    'activityImage' => $variables['app_url'] . '/images/notification-icon.png',
                    'facts' => [
                        [
                            'name' => 'Type',
                            'value' => ucfirst(str_replace('_', ' ', $notification->type)),
                        ],
                        [
                            'name' => 'Priority',
                            'value' => ucfirst($notification->priority),
                        ],
                        [
                            'name' => 'Time',
                            'value' => now()->format('Y-m-d H:i:s'),
                        ],
                    ],
                    'text' => $inApp['body'],
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'View Details',
                    'targets' => [
                        [
                            'os' => 'default',
                            'uri' => $variables['app_url'] . '/notifications/' . $notification->id,
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::post($webhookUrl, $payload);

        return $response->successful();
    }

    /**
     * Get Teams color based on priority.
     */
    protected function getTeamsColor(string $priority): string
    {
        return match ($priority) {
            Notification::PRIORITY_CRITICAL => '721c24',
            Notification::PRIORITY_HIGH => 'dc3545',
            Notification::PRIORITY_MEDIUM => 'ffc107',
            Notification::PRIORITY_LOW => '007bff',
            default => '6c757d',
        };
    }

    /**
     * Send webhook notification.
     */
    protected function sendWebhook(
        NotificationChannel $channel,
        Notification $notification,
        NotificationTemplate $template,
        array $variables
    ): bool {
        $url = $channel->getConfigValue('url');
        $method = $channel->getConfigValue('method', 'POST');
        $headers = $channel->getConfigValue('headers', []);
        $authType = $channel->getConfigValue('auth_type');

        // Get webhook payload from template
        $payload = $template->getWebhookPayload($variables);

        // Add notification data to payload
        $payload['notification'] = [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'priority' => $notification->priority,
            'created_at' => $notification->created_at->toIso8601String(),
        ];

        // Prepare request
        $request = Http::withHeaders($headers);

        // Add authentication
        switch ($authType) {
            case 'basic':
                $credentials = $channel->getConfigValue('auth_credentials', []);
                $request = $request->withBasicAuth(
                    $credentials['username'] ?? '',
                    $credentials['password'] ?? ''
                );
                break;

            case 'bearer':
                $token = $channel->getConfigValue('auth_credentials.token');
                $request = $request->withToken($token);
                break;

            case 'api_key':
                $credentials = $channel->getConfigValue('auth_credentials', []);
                $keyName = $credentials['key_name'] ?? 'X-API-Key';
                $keyValue = $credentials['key_value'] ?? '';
                $request = $request->withHeaders([$keyName => $keyValue]);
                break;
        }

        // Send request
        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $payload),
            'POST' => $request->post($url, $payload),
            'PUT' => $request->put($url, $payload),
            'PATCH' => $request->patch($url, $payload),
            default => $request->post($url, $payload),
        };

        return $response->successful();
    }

    /**
     * Create default channels for tenant.
     */
    public function createDefaultChannels(string $tenantId): void
    {
        // Create default email channel
        NotificationChannel::create([
            'tenant_id' => $tenantId,
            'type' => NotificationChannel::TYPE_EMAIL,
            'name' => 'Default Email Channel',
            'description' => 'Default email notification channel',
            'configuration' => [
                'smtp_host' => env('MAIL_HOST'),
                'smtp_port' => env('MAIL_PORT'),
                'smtp_username' => env('MAIL_USERNAME'),
                'smtp_password' => env('MAIL_PASSWORD') ? encrypt(env('MAIL_PASSWORD')) : null,
                'smtp_encryption' => env('MAIL_ENCRYPTION'),
                'from_email' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }
}