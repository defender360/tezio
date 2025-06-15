<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\NotificationChannel;
use App\Services\NotificationService;
use App\Services\TemplateService;
use App\Services\ChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;
    protected TemplateService $templateService;
    protected ChannelService $channelService;

    public function __construct(
        NotificationService $notificationService,
        TemplateService $templateService,
        ChannelService $channelService
    ) {
        $this->notificationService = $notificationService;
        $this->templateService = $templateService;
        $this->channelService = $channelService;
    }

    /**
     * Get notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Notification::where('user_id', $user->id)
            ->with('related');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->input('per_page', 15);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
            'statistics' => $this->notificationService->getUserStatistics($user),
        ]);
    }

    /**
     * Get a specific notification.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $notification = Notification::where('user_id', $user->id)
            ->with('related')
            ->findOrFail($id);

        // Mark as read automatically when viewed
        if ($notification->status === Notification::STATUS_UNREAD) {
            $notification->markAsRead();
        }

        return response()->json([
            'notification' => $notification,
        ]);
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'uuid',
        ]);

        $user = Auth::user();
        $count = $this->notificationService->markAsRead($user, $request->notification_ids);

        return response()->json([
            'message' => "{$count} notifications marked as read",
            'count' => $count,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'message' => "All notifications marked as read",
            'count' => $count,
        ]);
    }

    /**
     * Get notification statistics for the authenticated user.
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        // For now, return mock data since the models aren't ready
        return response()->json([
            'total' => 0,
            'unread' => 0,
            'read' => 0,
            'archived' => 0,
            'by_type' => [],
            'by_priority' => []
        ]);
    }

    /**
     * Archive notifications.
     */
    public function archive(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'uuid',
        ]);

        $user = Auth::user();
        $count = $this->notificationService->archive($user, $request->notification_ids);

        return response()->json([
            'message' => "{$count} notifications archived",
            'count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $notification = Notification::where('user_id', $user->id)
            ->findOrFail($id);
        
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get user notification preferences.
     */
    public function preferences(): JsonResponse
    {
        $user = Auth::user();
        
        $preferences = NotificationPreference::where('user_id', $user->id)->get();

        // Get all notification types and create default preferences for missing ones
        $notificationTypes = $this->getNotificationTypes();
        $existingTypes = $preferences->pluck('notification_type')->toArray();
        
        foreach ($notificationTypes as $type) {
            if (!in_array($type, $existingTypes)) {
                $preferences->push(NotificationPreference::create(array_merge(
                    NotificationPreference::getDefaultPreferences($type),
                    [
                        'tenant_id' => $user->tenant_id,
                        'user_id' => $user->id,
                        'notification_type' => $type,
                    ]
                )));
            }
        }

        return response()->json([
            'preferences' => $preferences,
            'available_channels' => NotificationChannel::getTypes(),
            'available_frequencies' => NotificationPreference::getFrequencies(),
        ]);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.enabled' => 'boolean',
            'preferences.*.channels' => 'array',
            'preferences.*.channels.*' => Rule::in(NotificationChannel::getTypes()),
            'preferences.*.frequency' => Rule::in(NotificationPreference::getFrequencies()),
            'preferences.*.filters' => 'nullable|array',
            'preferences.*.enable_quiet_hours' => 'boolean',
            'preferences.*.quiet_hours_start' => 'nullable|date_format:H:i',
            'preferences.*.quiet_hours_end' => 'nullable|date_format:H:i',
            'preferences.*.quiet_hours_days' => 'nullable|array',
            'preferences.*.quiet_hours_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'preferences.*.timezone' => 'nullable|timezone',
        ]);

        $user = Auth::user();
        $updated = [];

        DB::transaction(function () use ($request, $user, &$updated) {
            foreach ($request->preferences as $prefData) {
                $preference = NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $prefData['notification_type'],
                    ],
                    array_merge($prefData, ['tenant_id' => $user->tenant_id])
                );
                $updated[] = $preference;
            }
        });

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $updated,
        ]);
    }

    /**
     * Get notification templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = NotificationTemplate::where(function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id)
              ->orWhereNull('tenant_id');
        });

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('channel')) {
            $query->whereJsonContains('channels', $request->channel);
        }

        $templates = $query->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'templates' => $templates,
            'categories' => NotificationTemplate::getCategories(),
            'channels' => NotificationTemplate::getChannels(),
        ]);
    }

    /**
     * Get notification channels.
     */
    public function channels(): JsonResponse
    {
        $user = Auth::user();
        
        $channels = NotificationChannel::where('tenant_id', $user->tenant_id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'channels' => $channels->map(function ($channel) {
                // Hide sensitive configuration data
                $channel->configuration = array_map(function ($key) use ($channel) {
                    if (in_array($key, ['smtp_password', 'api_key', 'api_secret', 'auth_credentials'])) {
                        return '********';
                    }
                    return $channel->getConfigValue($key);
                }, array_keys($channel->configuration));
                
                return $channel;
            }),
            'types' => NotificationChannel::getTypes(),
        ]);
    }

    /**
     * Test notification channel.
     */
    public function testChannel(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => 'required|uuid',
        ]);

        $user = Auth::user();
        
        $channel = NotificationChannel::where('tenant_id', $user->tenant_id)
            ->findOrFail($request->channel_id);

        $result = $channel->testConnection();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'details' => $result,
        ]);
    }

    /**
     * Send test notification.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'channels' => 'required|array',
            'channels.*' => Rule::in(NotificationChannel::getTypes()),
        ]);

        $user = Auth::user();

        // Create test notification
        $notification = $this->notificationService->send(
            $user,
            $request->type,
            [
                'test' => true,
                'message' => 'This is a test notification',
                'timestamp' => now()->toIso8601String(),
            ],
            null,
            null,
            Notification::PRIORITY_LOW
        );

        return response()->json([
            'message' => 'Test notification sent successfully',
            'notification' => $notification,
        ]);
    }

    /**
     * Get available notification types.
     */
    protected function getNotificationTypes(): array
    {
        return [
            'incident_created',
            'incident_updated',
            'incident_resolved',
            'incident_assigned',
            'sla_breach',
            'sla_warning',
            'change_requested',
            'change_approved',
            'change_rejected',
            'change_completed',
            'problem_created',
            'problem_resolved',
            'knowledge_article_published',
            'service_request_created',
            'service_request_completed',
            'system_announcement',
            'maintenance_scheduled',
        ];
    }
}