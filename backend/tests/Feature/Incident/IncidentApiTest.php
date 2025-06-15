<?php

namespace Tests\Feature\Incident;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class IncidentApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'API Test Tenant',
            'subdomain' => 'apitest',
        ]);
        
        // Create user
        $this->user = User::create([
            'name' => 'API Test User',
            'email' => 'api@test.com',
            'auth0_id' => 'auth0|apitest',
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Set tenant context
        app()->instance('current_tenant', $this->tenant);
        
        // Mock Auth0 middleware for testing
        $this->withoutMiddleware(\App\Http\Middleware\Auth0Middleware::class);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function can_list_incidents()
    {
        // Create test incidents
        Incident::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
        ]);
        
        $response = $this->getJson('/api/v1/incidents');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'created_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function can_create_incident_via_api()
    {
        $incidentData = [
            'title' => 'API Created Incident',
            'description' => 'This incident was created via API',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'API Test',
            'tags' => ['api', 'test'],
        ];
        
        $response = $this->postJson('/api/v1/incidents', $incidentData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'status',
                    'sla_deadline',
                ]
            ]);
        
        $this->assertDatabaseHas('incidents', [
            'title' => 'API Created Incident',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function can_update_incident_via_api()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'priority' => Incident::PRIORITY_LOW,
            'impact' => Incident::IMPACT_LOW,
            'urgency' => 'low',
            'category' => 'Test',
            'created_by' => $this->user->id,
        ]);
        
        $updateData = [
            'title' => 'Updated via API',
            'priority' => Incident::PRIORITY_CRITICAL,
        ];
        
        $response = $this->putJson("/api/v1/incidents/{$incident->id}", $updateData);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Incident updated successfully',
                'data' => [
                    'title' => 'Updated via API',
                    'priority' => Incident::PRIORITY_CRITICAL,
                ]
            ]);
    }

    /** @test */
    public function can_add_comment_via_api()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Comment Test',
            'description' => 'Testing comments',
            'priority' => Incident::PRIORITY_MEDIUM,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'medium',
            'category' => 'Test',
            'created_by' => $this->user->id,
        ]);
        
        $commentData = [
            'comment' => 'This is a test comment via API',
            'is_internal' => false,
        ];
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", $commentData);
        
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Comment added successfully',
                'data' => [
                    'comment' => 'This is a test comment via API',
                ]
            ]);
    }

    /** @test */
    public function can_resolve_incident_via_api()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'To Be Resolved',
            'description' => 'This will be resolved',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'Test',
            'created_by' => $this->user->id,
            'status' => Incident::STATUS_IN_PROGRESS,
        ]);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/resolve", [
            'resolution_notes' => 'Resolved via API test',
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Incident resolved successfully',
                'data' => [
                    'status' => Incident::STATUS_RESOLVED,
                    'resolution_notes' => 'Resolved via API test',
                ]
            ]);
    }

    /** @test */
    public function can_get_incident_metrics()
    {
        // Create incidents with different statuses
        Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Open Incident',
            'description' => 'Test',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'Test',
            'created_by' => $this->user->id,
            'status' => Incident::STATUS_OPEN,
        ]);
        
        Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'In Progress Incident',
            'description' => 'Test',
            'priority' => Incident::PRIORITY_MEDIUM,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'medium',
            'category' => 'Test',
            'created_by' => $this->user->id,
            'status' => Incident::STATUS_IN_PROGRESS,
        ]);
        
        $response = $this->getJson('/api/v1/incidents/metrics/summary');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_open',
                'total_in_progress',
                'total_resolved',
                'total_overdue',
                'by_priority',
                'by_category',
            ]);
    }

    /** @test */
    public function validation_errors_return_422()
    {
        $invalidData = [
            'title' => '', // Required field
            'priority' => 'invalid', // Invalid enum value
        ];
        
        $response = $this->postJson('/api/v1/incidents', $invalidData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'priority']);
    }
}