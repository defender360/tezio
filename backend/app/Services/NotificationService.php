<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected TemplateService $templateService;
    protected ChannelService $channelService;

    public function __construct(TemplateService $templateService, ChannelService $channelService)
    {
        $this->templateService = $templateService;
        $this->channelService = $channelService;
    }

    /**
     * Send notification to a single user.
     */
    public function send(
        User $user,
        string $type,
        array $data = [],
        ?string $relatedType = null,
        ?string $relatedId = null,
        string $priority = Notification::PRIORITY_MEDIUM
    ): ?Notification {
        try {
            // Get user preferences
            $preference = NotificationPreference::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->first();

            if (!$preference) {
                // Create default preferences
                $preference = NotificationPreference::create(array_merge(
                    NotificationPreference::getDefaultPreferences($type),
                    [
                        'tenant_id' => $user->tenant_id,
                        'user_id' => $user->id,
                        'notification_type' => $type,
                    ]
                ));
            }

            // Check if should send
            if (!$preference->shouldSendNotification(array_merge($data, ['priority' => $priority]))) {
                return null;
            }

            // Get template
            $template = $this->templateService->getTemplate($type, $user->tenant_id);
            if (!$template || !$template->is_active) {
                Log::warning("No active template found for notification type: {$type}");
                return null;
            }

            // Prepare variables
            $variables = $this->prepareVariables($user, $data, $relatedType, $relatedId);

            // Create notification record
            $notification = Notification::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'type' => $type,
                'title' => $template->renderInApp($variables)['title'] ?? "New {$type} notification",
                'body' => $template->renderInApp($variables)['body'] ?? '',
                'data' => $data,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'priority' => $priority,
                'status' => Notification::STATUS_UNREAD,
            ]);

            // Send through enabled channels
            $this->sendThroughChannels($notification, $template, $preference, $variables);

            // Mark as sent
            $notification->update(['sent_at' => now()]);

            // Broadcast real-time notification
            $this->broadcastNotification($notification);

            return $notification;
        } catch (\Exception $e) {
            Log::error("Failed to send notification: {$e->getMessage()}", [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendBulk(
        Collection $users,
        string $type,
        array $data = [],
        ?string $relatedType = null,
        ?string $relatedId = null,
        string $priority = Notification::PRIORITY_MEDIUM
    ): array {
        $results = [];

        foreach ($users as $user) {
            $notification = $this->send($user, $type, $data, $relatedType, $relatedId, $priority);
            if ($notification) {
                $results[] = $notification;
            }
        }

        return $results;
    }

    /**
     * Send notification to users by role.
     */
    public function sendToRole(
        string $role,
        string $tenantId,
        string $type,
        array $data = [],
        ?string $relatedType = null,
        ?string $relatedId = null,
        string $priority = Notification::PRIORITY_MEDIUM
    ): array {
        $users = User::where('tenant_id', $tenantId)
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })
            ->get();

        return $this->sendBulk($users, $type, $data, $relatedType, $relatedId, $priority);
    }

    /**
     * Send notification through enabled channels.
     */
    protected function sendThroughChannels(
        Notification $notification,
        NotificationTemplate $template,
        NotificationPreference $preference,
        array $variables
    ): void {
        $enabledChannels = $preference->getEnabledChannels();

        foreach ($enabledChannels as $channelType) {
            if (!$template->supportsChannel($channelType)) {
                continue;
            }

            try {
                $this->channelService->send($channelType, $notification, $template, $variables);
            } catch (\Exception $e) {
                Log::error("Failed to send notification through channel: {$channelType}", [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Prepare variables for template rendering.
     */
    protected function prepareVariables(User $user, array $data, ?string $relatedType, ?string $relatedId): array
    {
        $variables = array_merge($data, [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'tenant_name' => $user->tenant->name ?? '',
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
        ]);

        // Add related model data
        if ($relatedType && $relatedId) {
            $relatedModel = $this->getRelatedModel($relatedType, $relatedId);
            if ($relatedModel) {
                $variables['related'] = $relatedModel->toArray();
            }
        }

        return $variables;
    }

    /**
     * Get related model instance.
     */
    protected function getRelatedModel(string $type, string $id)
    {
        $modelClass = match ($type) {
            'incident' => \App\Domains\Incident\Models\Incident::class,
            'change' => \App\Models\Change::class,
            'problem' => \App\Models\Problem::class,
            'service_request' => \App\Models\ServiceRequest::class,
            'knowledge_article' => \App\Models\KnowledgeArticle::class,
            default => null,
        };

        return $modelClass ? $modelClass::find($id) : null;
    }

    /**
     * Broadcast real-time notification.
     */
    protected function broadcastNotification(Notification $notification): void
    {
        try {
            broadcast(new \App\Events\NotificationCreated($notification))->toOthers();
        } catch (\Exception $e) {
            Log::warning("Failed to broadcast notification: {$e->getMessage()}");
        }
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(User $user, array $notificationIds): int
    {
        return Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->where('status', Notification::STATUS_UNREAD)
            ->update([
                'status' => Notification::STATUS_READ,
                'read_at' => now(),
            ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('status', Notification::STATUS_UNREAD)
            ->update([
                'status' => Notification::STATUS_READ,
                'read_at' => now(),
            ]);
    }

    /**
     * Archive notifications.
     */
    public function archive(User $user, array $notificationIds): int
    {
        return Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->update(['status' => Notification::STATUS_ARCHIVED]);
    }

    /**
     * Get notification statistics for a user.
     */
    public function getUserStatistics(User $user): array
    {
        $stats = Notification::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'unread' => $stats[Notification::STATUS_UNREAD] ?? 0,
            'read' => $stats[Notification::STATUS_READ] ?? 0,
            'archived' => $stats[Notification::STATUS_ARCHIVED] ?? 0,
            'total' => array_sum($stats),
        ];
    }

    /**
     * Clean up old notifications.
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysToKeep))
            ->where('status', '!=', Notification::STATUS_UNREAD)
            ->delete();
    }
}