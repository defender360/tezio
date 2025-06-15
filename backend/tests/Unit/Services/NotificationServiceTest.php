<?php

namespace Tests\Unit\Services;

use App\Services\NotificationService;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
        $this->artisan('migrate');
    }

    public function test_can_send_notification_to_user()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $template = NotificationTemplate::factory()->create([
            'name' => 'incident.created',
            'subject' => 'New Incident: {{ incident.title }}',
            'body' => 'A new incident has been created: {{ incident.title }}'
        ]);
        
        $data = [
            'incident' => [
                'title' => 'Server Down',
                'number' => 'INC-2024-000001'
            ]
        ];
        
        $notification = $this->service->send($user, 'incident.created', $data);
        
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('incident.created', $notification->type);
        $this->assertEquals('New Incident: Server Down', $notification->title);
        $this->assertEquals('A new incident has been created: Server Down', $notification->message);
        $this->assertEquals($data, $notification->data);
        
        Queue::assertPushed(\App\Jobs\SendNotificationJob::class);
    }

    public function test_can_send_notification_to_multiple_users()
    {
        Queue::fake();
        
        $users = User::factory()->count(3)->create();
        $template = NotificationTemplate::factory()->create([
            'name' => 'maintenance.scheduled',
            'subject' => 'Scheduled Maintenance',
            'body' => 'Maintenance window scheduled for {{ date }}'
        ]);
        
        $data = ['date' => '2024-01-20 02:00 AM'];
        
        $notifications = $this->service->sendToMany($users, 'maintenance.scheduled', $data);
        
        $this->assertCount(3, $notifications);
        foreach ($notifications as $notification) {
            $this->assertEquals('maintenance.scheduled', $notification->type);
            $this->assertEquals('Scheduled Maintenance', $notification->title);
        }
        
        Queue::assertPushed(\App\Jobs\SendNotificationJob::class, 3);
    }

    public function test_respects_user_notification_preferences()
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        // User has disabled email notifications for incidents
        NotificationPreference::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'type' => 'incident',
            'enabled' => false
        ]);
        
        // But enabled in-app notifications
        NotificationPreference::create([
            'user_id' => $user->id,
            'channel' => 'in_app',
            'type' => 'incident',
            'enabled' => true
        ]);
        
        $template = NotificationTemplate::factory()->create([
            'name' => 'incident.assigned',
            'channels' => ['email', 'in_app']
        ]);
        
        $notification = $this->service->send($user, 'incident.assigned', []);
        
        // Should create in-app notification
        $this->assertNotNull($notification);
        
        // Should not send email
        Mail::assertNothingSent();
    }

    public function test_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null
        ]);
        
        $this->service->markAsRead($notification);
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now()
        ]);
        
        $count = $this->service->markAllAsRead($user);
        
        $this->assertEquals(3, $count);
        $this->assertEquals(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_can_get_unread_count()
    {
        $user = User::factory()->create();
        
        Notification::factory()->count(4)->create([
            'user_id' => $user->id,
            'read_at' => null
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now()
        ]);
        
        $count = $this->service->getUnreadCount($user);
        
        $this->assertEquals(4, $count);
    }

    public function test_can_archive_notifications()
    {
        $user = User::factory()->create();
        
        $notifications = Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'archived_at' => null
        ]);
        
        $this->service->archive($notifications->pluck('id')->toArray());
        
        $this->assertEquals(3, Notification::whereNotNull('archived_at')->count());
    }

    public function test_notification_template_variables_are_replaced()
    {
        $template = new NotificationTemplate([
            'subject' => 'Hello {{ user.name }}',
            'body' => 'Your ticket {{ ticket.number }} has been updated by {{ agent.name }}'
        ]);
        
        $data = [
            'user' => ['name' => 'John Doe'],
            'ticket' => ['number' => 'INC-001'],
            'agent' => ['name' => 'Jane Smith']
        ];
        
        $parsed = $this->service->parseTemplate($template, $data);
        
        $this->assertEquals('Hello John Doe', $parsed['subject']);
        $this->assertEquals('Your ticket INC-001 has been updated by Jane Smith', $parsed['body']);
    }

    public function test_can_schedule_notification_for_later()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $scheduledFor = now()->addHours(2);
        
        $notification = $this->service->schedule(
            $user,
            'reminder',
            ['message' => 'Don\'t forget!'],
            $scheduledFor
        );
        
        $this->assertEquals($scheduledFor->toDateTimeString(), $notification->scheduled_for->toDateTimeString());
        $this->assertNull($notification->sent_at);
        
        Queue::assertPushed(\App\Jobs\SendScheduledNotificationJob::class);
    }

    public function test_can_get_notification_statistics()
    {
        $user = User::factory()->create();
        
        // Create various notifications
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'type' => 'incident.created',
            'read_at' => null
        ]);
        
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'incident.updated',
            'read_at' => now()
        ]);
        
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'type' => 'incident.resolved',
            'archived_at' => now()
        ]);
        
        $stats = $this->service->getStatistics($user);
        
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['unread']);
        $this->assertEquals(3, $stats['read']);
        $this->assertEquals(2, $stats['archived']);
        $this->assertEquals(5, $stats['by_type']['incident.created']);
        $this->assertEquals(3, $stats['by_type']['incident.updated']);
        $this->assertEquals(2, $stats['by_type']['incident.resolved']);
    }

    public function test_notification_channels_are_configurable()
    {
        $emailChannel = NotificationChannel::factory()->create([
            'name' => 'email',
            'driver' => 'mail',
            'enabled' => true,
            'config' => ['from' => 'noreply@example.com']
        ]);
        
        $smsChannel = NotificationChannel::factory()->create([
            'name' => 'sms',
            'driver' => 'twilio',
            'enabled' => false,
            'config' => ['account_sid' => 'xxx', 'auth_token' => 'yyy']
        ]);
        
        $channels = $this->service->getAvailableChannels();
        
        $this->assertCount(1, $channels);
        $this->assertEquals('email', $channels->first()->name);
    }

    public function test_can_test_notification_channel()
    {
        Mail::fake();
        
        $channel = NotificationChannel::factory()->create([
            'name' => 'email',
            'driver' => 'mail',
            'enabled' => true
        ]);
        
        $result = $this->service->testChannel($channel, 'test@example.com');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Test notification sent successfully', $result['message']);
        
        Mail::assertSent(\App\Mail\TestNotification::class);
    }
}