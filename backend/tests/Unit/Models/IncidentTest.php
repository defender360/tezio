<?php

namespace Tests\Unit\Models;

use App\Models\Incident;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_incident_has_fillable_attributes()
    {
        $fillable = [
            'title', 'description', 'priority', 'status', 'impact', 'urgency',
            'assigned_to', 'assigned_group', 'category', 'subcategory',
            'resolution', 'resolved_at', 'closed_at', 'sla_response_target',
            'sla_resolution_target', 'sla_breached', 'tenant_id', 'reporter_id'
        ];

        $incident = new Incident();
        $this->assertEquals($fillable, $incident->getFillable());
    }

    public function test_incident_generates_number_on_creation()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $incident = Incident::factory()->create([
            'tenant_id' => $tenant->id,
            'reporter_id' => $user->id
        ]);

        $this->assertNotNull($incident->number);
        $this->assertMatchesRegularExpression('/^INC-\d{4}-\d{6}$/', $incident->number);
    }

    public function test_incident_calculates_priority_from_impact_and_urgency()
    {
        $incident = new Incident();
        
        // Test critical priority
        $incident->impact = 'high';
        $incident->urgency = 'high';
        $this->assertEquals('critical', $incident->calculatePriority());
        
        // Test high priority
        $incident->impact = 'high';
        $incident->urgency = 'medium';
        $this->assertEquals('high', $incident->calculatePriority());
        
        // Test medium priority
        $incident->impact = 'medium';
        $incident->urgency = 'medium';
        $this->assertEquals('medium', $incident->calculatePriority());
        
        // Test low priority
        $incident->impact = 'low';
        $incident->urgency = 'low';
        $this->assertEquals('low', $incident->calculatePriority());
    }

    public function test_incident_belongs_to_tenant()
    {
        $tenant = Tenant::factory()->create();
        $incident = Incident::factory()->create(['tenant_id' => $tenant->id]);
        
        $this->assertInstanceOf(Tenant::class, $incident->tenant);
        $this->assertEquals($tenant->id, $incident->tenant->id);
    }

    public function test_incident_belongs_to_reporter()
    {
        $user = User::factory()->create();
        $incident = Incident::factory()->create(['reporter_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $incident->reporter);
        $this->assertEquals($user->id, $incident->reporter->id);
    }

    public function test_incident_can_be_assigned_to_user()
    {
        $assignee = User::factory()->create();
        $incident = Incident::factory()->create(['assigned_to' => $assignee->id]);
        
        $this->assertInstanceOf(User::class, $incident->assignee);
        $this->assertEquals($assignee->id, $incident->assignee->id);
    }

    public function test_incident_has_many_comments()
    {
        $incident = Incident::factory()->create();
        $user = User::factory()->create();
        
        $comment1 = $incident->comments()->create([
            'content' => 'First comment',
            'user_id' => $user->id
        ]);
        
        $comment2 = $incident->comments()->create([
            'content' => 'Second comment',
            'user_id' => $user->id
        ]);
        
        $this->assertCount(2, $incident->comments);
        $this->assertTrue($incident->comments->contains($comment1));
        $this->assertTrue($incident->comments->contains($comment2));
    }

    public function test_incident_has_many_attachments()
    {
        $incident = Incident::factory()->create();
        
        $attachment1 = $incident->attachments()->create([
            'filename' => 'test1.pdf',
            'file_path' => 'attachments/test1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf'
        ]);
        
        $attachment2 = $incident->attachments()->create([
            'filename' => 'test2.jpg',
            'file_path' => 'attachments/test2.jpg',
            'file_size' => 2048,
            'mime_type' => 'image/jpeg'
        ]);
        
        $this->assertCount(2, $incident->attachments);
        $this->assertTrue($incident->attachments->contains($attachment1));
        $this->assertTrue($incident->attachments->contains($attachment2));
    }

    public function test_incident_tracks_history()
    {
        $incident = Incident::factory()->create(['status' => 'new']);
        $user = User::factory()->create();
        
        // Change status
        $incident->update(['status' => 'in_progress']);
        $incident->history()->create([
            'field' => 'status',
            'old_value' => 'new',
            'new_value' => 'in_progress',
            'user_id' => $user->id
        ]);
        
        // Change assignee
        $incident->update(['assigned_to' => $user->id]);
        $incident->history()->create([
            'field' => 'assigned_to',
            'old_value' => null,
            'new_value' => $user->id,
            'user_id' => $user->id
        ]);
        
        $this->assertCount(2, $incident->history);
        $this->assertEquals('status', $incident->history->first()->field);
        $this->assertEquals('assigned_to', $incident->history->last()->field);
    }

    public function test_incident_scopes_by_status()
    {
        Incident::factory()->create(['status' => 'new']);
        Incident::factory()->create(['status' => 'in_progress']);
        Incident::factory()->create(['status' => 'in_progress']);
        Incident::factory()->create(['status' => 'resolved']);
        Incident::factory()->create(['status' => 'closed']);
        
        $this->assertEquals(1, Incident::whereStatus('new')->count());
        $this->assertEquals(2, Incident::whereStatus('in_progress')->count());
        $this->assertEquals(1, Incident::whereStatus('resolved')->count());
        $this->assertEquals(1, Incident::whereStatus('closed')->count());
        $this->assertEquals(3, Incident::whereIn('status', ['new', 'in_progress'])->count());
    }

    public function test_incident_scopes_by_priority()
    {
        Incident::factory()->create(['priority' => 'critical']);
        Incident::factory()->create(['priority' => 'high']);
        Incident::factory()->create(['priority' => 'high']);
        Incident::factory()->create(['priority' => 'medium']);
        Incident::factory()->create(['priority' => 'low']);
        
        $this->assertEquals(1, Incident::wherePriority('critical')->count());
        $this->assertEquals(2, Incident::wherePriority('high')->count());
        $this->assertEquals(3, Incident::whereIn('priority', ['critical', 'high'])->count());
    }

    public function test_incident_tracks_sla_breach()
    {
        $now = now();
        
        // Create incident with breached SLA
        $breachedIncident = Incident::factory()->create([
            'sla_response_target' => $now->subHours(2),
            'sla_resolution_target' => $now->subHour(),
            'sla_breached' => true
        ]);
        
        // Create incident within SLA
        $withinSlaIncident = Incident::factory()->create([
            'sla_response_target' => $now->addHours(2),
            'sla_resolution_target' => $now->addHours(4),
            'sla_breached' => false
        ]);
        
        $this->assertTrue($breachedIncident->sla_breached);
        $this->assertFalse($withinSlaIncident->sla_breached);
        
        $this->assertEquals(1, Incident::where('sla_breached', true)->count());
        $this->assertEquals(1, Incident::where('sla_breached', false)->count());
    }

    public function test_incident_can_be_resolved()
    {
        $incident = Incident::factory()->create(['status' => 'in_progress']);
        $resolver = User::factory()->create();
        
        $incident->resolve('Applied patch and restarted service', $resolver);
        
        $this->assertEquals('resolved', $incident->status);
        $this->assertEquals('Applied patch and restarted service', $incident->resolution);
        $this->assertNotNull($incident->resolved_at);
        $this->assertEquals($resolver->id, $incident->resolved_by);
    }

    public function test_incident_can_be_closed()
    {
        $incident = Incident::factory()->create(['status' => 'resolved']);
        
        $incident->close();
        
        $this->assertEquals('closed', $incident->status);
        $this->assertNotNull($incident->closed_at);
    }

    public function test_incident_calculates_time_to_resolve()
    {
        $createdAt = now()->subHours(4);
        $resolvedAt = now();
        
        $incident = Incident::factory()->create([
            'created_at' => $createdAt,
            'resolved_at' => $resolvedAt
        ]);
        
        $this->assertEquals(4 * 60, $incident->getTimeToResolveInMinutes());
        $this->assertEquals('4 hours', $incident->getTimeToResolveForHumans());
    }

    public function test_incident_can_check_if_overdue()
    {
        $overdueIncident = Incident::factory()->create([
            'sla_resolution_target' => now()->subHour(),
            'status' => 'in_progress'
        ]);
        
        $notOverdueIncident = Incident::factory()->create([
            'sla_resolution_target' => now()->addHour(),
            'status' => 'in_progress'
        ]);
        
        $resolvedIncident = Incident::factory()->create([
            'sla_resolution_target' => now()->subHour(),
            'status' => 'resolved'
        ]);
        
        $this->assertTrue($overdueIncident->isOverdue());
        $this->assertFalse($notOverdueIncident->isOverdue());
        $this->assertFalse($resolvedIncident->isOverdue());
    }
}