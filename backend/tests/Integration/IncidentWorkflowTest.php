<?php

namespace Tests\Integration;

use App\Models\Incident;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SLA;
use App\Services\NotificationService;
use App\Events\NotificationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IncidentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $reporter;
    private User $technician;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        
        $this->reporter = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'user'
        ]);
        
        $this->technician = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'technician'
        ]);
        
        $this->manager = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'manager'
        ]);
        
        // Create SLA rules
        SLA::create([
            'name' => 'Critical - 1 hour',
            'priority' => 'critical',
            'response_time' => 60, // minutes
            'resolution_time' => 240,
            'tenant_id' => $this->tenant->id
        ]);
        
        SLA::create([
            'name' => 'High - 4 hours',
            'priority' => 'high',
            'response_time' => 240,
            'resolution_time' => 480,
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_complete_incident_lifecycle()
    {
        Event::fake();
        Queue::fake();
        
        // Step 1: User creates an incident
        $this->actingAs($this->reporter);
        
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Production server is down',
            'description' => 'The main production server is not responding',
            'impact' => 'high',
            'urgency' => 'high',
            'category' => 'infrastructure',
            'subcategory' => 'server'
        ]);
        
        $response->assertStatus(201);
        $incident = Incident::find($response->json('data.id'));
        
        // Verify incident was created correctly
        $this->assertEquals('critical', $incident->priority);
        $this->assertEquals('new', $incident->status);
        $this->assertNotNull($incident->sla_response_target);
        $this->assertNotNull($incident->sla_resolution_target);
        
        // Verify notifications were triggered
        Event::assertDispatched(NotificationCreated::class);
        
        // Step 2: Technician acknowledges the incident
        $this->actingAs($this->technician);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/assign", [
            'user_id' => $this->technician->id
        ]);
        
        $response->assertStatus(200);
        
        $response = $this->putJson("/api/v1/incidents/{$incident->id}", [
            'status' => 'in_progress'
        ]);
        
        $response->assertStatus(200);
        $incident->refresh();
        
        $this->assertEquals('in_progress', $incident->status);
        $this->assertEquals($this->technician->id, $incident->assigned_to);
        
        // Step 3: Technician adds investigation notes
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", [
            'content' => 'Investigating the issue. Server appears to be offline.',
            'is_private' => false
        ]);
        
        $response->assertStatus(201);
        
        // Step 4: Technician identifies root cause and implements fix
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", [
            'content' => 'Found the issue: disk space full. Cleaning up old logs.',
            'is_private' => false
        ]);
        
        $response->assertStatus(201);
        
        // Step 5: Technician resolves the incident
        $response = $this->putJson("/api/v1/incidents/{$incident->id}", [
            'status' => 'resolved',
            'resolution' => 'Cleared old log files to free up disk space. Implemented log rotation to prevent recurrence.'
        ]);
        
        $response->assertStatus(200);
        $incident->refresh();
        
        $this->assertEquals('resolved', $incident->status);
        $this->assertNotNull($incident->resolution);
        $this->assertNotNull($incident->resolved_at);
        
        // Step 6: User confirms resolution
        $this->actingAs($this->reporter);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", [
            'content' => 'Confirmed, server is back online. Thank you!',
            'is_private' => false
        ]);
        
        $response->assertStatus(201);
        
        // Step 7: System automatically closes the incident after confirmation
        $response = $this->putJson("/api/v1/incidents/{$incident->id}", [
            'status' => 'closed'
        ]);
        
        $response->assertStatus(200);
        $incident->refresh();
        
        $this->assertEquals('closed', $incident->status);
        $this->assertNotNull($incident->closed_at);
        
        // Verify audit trail
        $this->assertCount(4, $incident->history); // Created, Assigned, In Progress, Resolved, Closed
        $this->assertCount(4, $incident->comments); // 4 comments added during workflow
    }

    public function test_sla_breach_escalation()
    {
        Event::fake();
        
        // Create a critical incident
        $incident = Incident::factory()->create([
            'tenant_id' => $this->tenant->id,
            'priority' => 'critical',
            'status' => 'new',
            'sla_response_target' => now()->subMinutes(30), // Already breached
            'sla_resolution_target' => now()->addHours(3)
        ]);
        
        // Run SLA check job
        $this->artisan('sla:check');
        
        // Verify incident was marked as breached
        $incident->refresh();
        $this->assertTrue($incident->sla_breached);
        
        // Verify escalation notification was sent to manager
        Event::assertDispatched(NotificationCreated::class, function ($event) {
            return $event->notification->type === 'sla.breach.escalation';
        });
    }

    public function test_incident_auto_assignment()
    {
        // Configure auto-assignment rules
        config([
            'incidents.auto_assignment.enabled' => true,
            'incidents.auto_assignment.rules' => [
                [
                    'category' => 'infrastructure',
                    'subcategory' => 'server',
                    'assign_to_group' => 'Server Team'
                ]
            ]
        ]);
        
        $this->actingAs($this->reporter);
        
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Server performance issue',
            'description' => 'Server responding slowly',
            'impact' => 'medium',
            'urgency' => 'medium',
            'category' => 'infrastructure',
            'subcategory' => 'server'
        ]);
        
        $response->assertStatus(201);
        $incident = Incident::find($response->json('data.id'));
        
        // Verify auto-assignment
        $this->assertEquals('Server Team', $incident->assigned_group);
    }

    public function test_incident_creates_problem_after_threshold()
    {
        Event::fake();
        
        // Create multiple similar incidents
        for ($i = 0; $i < 5; $i++) {
            Incident::factory()->create([
                'tenant_id' => $this->tenant->id,
                'title' => 'Database connection timeout',
                'category' => 'application',
                'subcategory' => 'database',
                'status' => 'resolved'
            ]);
        }
        
        // Create one more to trigger problem creation
        $this->actingAs($this->technician);
        
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Database connection timeout',
            'description' => 'Another database timeout error',
            'impact' => 'medium',
            'urgency' => 'medium',
            'category' => 'application',
            'subcategory' => 'database'
        ]);
        
        $response->assertStatus(201);
        
        // Verify problem was created
        $this->assertDatabaseHas('problems', [
            'title' => 'Recurring: Database connection timeout',
            'tenant_id' => $this->tenant->id
        ]);
        
        // Verify notification was sent
        Event::assertDispatched(NotificationCreated::class, function ($event) {
            return $event->notification->type === 'problem.created.from.incidents';
        });
    }

    public function test_incident_knowledge_base_integration()
    {
        // Create a knowledge article
        $article = \App\Models\KnowledgeArticle::factory()->create([
            'title' => 'How to resolve disk space issues',
            'content' => 'Steps to clean up disk space...',
            'tags' => ['disk', 'space', 'storage'],
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->actingAs($this->reporter);
        
        // Create incident with keywords matching the article
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Server disk space full',
            'description' => 'Getting disk space errors on production server',
            'impact' => 'high',
            'urgency' => 'medium',
            'category' => 'infrastructure',
            'subcategory' => 'storage'
        ]);
        
        $response->assertStatus(201);
        $incidentId = $response->json('data.id');
        
        // Check for suggested knowledge articles
        $response = $this->getJson("/api/v1/incidents/{$incidentId}/knowledge-suggestions");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $article->id)
            ->assertJsonPath('data.0.title', 'How to resolve disk space issues');
    }

    public function test_incident_change_request_creation()
    {
        $this->actingAs($this->technician);
        
        // Create and resolve an incident
        $incident = Incident::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Application crash due to memory leak',
            'priority' => 'high',
            'status' => 'resolved',
            'resolution' => 'Restarted application. Need to update to fix memory leak.'
        ]);
        
        // Create change request from incident
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/create-change", [
            'title' => 'Update application to fix memory leak',
            'description' => 'Update to version 2.5.1 which includes memory leak fix',
            'type' => 'standard',
            'risk' => 'medium',
            'impact' => 'medium',
            'scheduled_start' => now()->addDays(3)->toDateTimeString(),
            'scheduled_end' => now()->addDays(3)->addHours(2)->toDateTimeString()
        ]);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.related_incident_id', $incident->id);
        
        $this->assertDatabaseHas('changes', [
            'title' => 'Update application to fix memory leak',
            'related_incident_id' => $incident->id,
            'tenant_id' => $this->tenant->id
        ]);
    }
}