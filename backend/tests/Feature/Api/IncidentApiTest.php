<?php

namespace Tests\Feature\Api;

use App\Models\Incident;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IncidentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_can_list_incidents()
    {
        // Create incidents for the user's tenant
        Incident::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        
        // Create incidents for another tenant (should not be visible)
        $otherTenant = Tenant::factory()->create();
        Incident::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->getJson('/api/v1/incidents');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'title',
                        'description',
                        'priority',
                        'status',
                        'impact',
                        'urgency',
                        'reporter',
                        'assignee',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ]
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_incidents_by_status()
    {
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'new']);
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'in_progress']);
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'resolved']);
        
        $response = $this->getJson('/api/v1/incidents?status=in_progress');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'in_progress');
    }

    public function test_can_filter_incidents_by_priority()
    {
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'priority' => 'low']);
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'priority' => 'medium']);
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'priority' => 'high']);
        Incident::factory()->create(['tenant_id' => $this->tenant->id, 'priority' => 'critical']);
        
        $response = $this->getJson('/api/v1/incidents?priority=high,critical');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_incidents()
    {
        Incident::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Email server is down',
            'description' => 'Users cannot send or receive emails'
        ]);
        
        Incident::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Database connection timeout',
            'description' => 'Application experiencing slow response times'
        ]);
        
        $response = $this->getJson('/api/v1/incidents?search=email');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Email server is down');
    }

    public function test_can_create_incident()
    {
        $data = [
            'title' => 'New server issue',
            'description' => 'Server is not responding to requests',
            'impact' => 'high',
            'urgency' => 'high',
            'category' => 'hardware',
            'subcategory' => 'server'
        ];
        
        $response = $this->postJson('/api/v1/incidents', $data);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'title',
                    'description',
                    'priority',
                    'status',
                    'impact',
                    'urgency',
                    'category',
                    'subcategory',
                    'reporter' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'created_at'
                ]
            ])
            ->assertJsonPath('data.title', 'New server issue')
            ->assertJsonPath('data.priority', 'critical')
            ->assertJsonPath('data.status', 'new');
        
        $this->assertDatabaseHas('incidents', [
            'title' => 'New server issue',
            'tenant_id' => $this->tenant->id,
            'reporter_id' => $this->user->id
        ]);
    }

    public function test_create_incident_validation()
    {
        $response = $this->postJson('/api/v1/incidents', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'impact', 'urgency']);
    }

    public function test_can_view_incident()
    {
        $incident = Incident::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->getJson("/api/v1/incidents/{$incident->id}");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $incident->id)
            ->assertJsonPath('data.number', $incident->number)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'title',
                    'description',
                    'priority',
                    'status',
                    'comments_count',
                    'attachments_count',
                    'history' => [
                        '*' => [
                            'field',
                            'old_value',
                            'new_value',
                            'changed_by',
                            'changed_at'
                        ]
                    ]
                ]
            ]);
    }

    public function test_cannot_view_incident_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $incident = Incident::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $response = $this->getJson("/api/v1/incidents/{$incident->id}");
        
        $response->assertStatus(404);
    }

    public function test_can_update_incident()
    {
        $incident = Incident::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'new'
        ]);
        
        $data = [
            'title' => 'Updated title',
            'status' => 'in_progress',
            'assigned_to' => $this->user->id
        ];
        
        $response = $this->putJson("/api/v1/incidents/{$incident->id}", $data);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.assignee.id', $this->user->id);
        
        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'title' => 'Updated title',
            'status' => 'in_progress'
        ]);
    }

    public function test_can_assign_incident()
    {
        $incident = Incident::factory()->create(['tenant_id' => $this->tenant->id]);
        $assignee = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/assign", [
            'user_id' => $assignee->id,
            'group' => 'IT Support'
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.assignee.id', $assignee->id)
            ->assertJsonPath('data.assigned_group', 'IT Support');
    }

    public function test_can_add_comment_to_incident()
    {
        $incident = Incident::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/comments", [
            'content' => 'This is a test comment',
            'is_private' => false
        ]);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'This is a test comment')
            ->assertJsonPath('data.user.id', $this->user->id);
        
        $this->assertDatabaseHas('incident_comments', [
            'incident_id' => $incident->id,
            'content' => 'This is a test comment',
            'user_id' => $this->user->id
        ]);
    }

    public function test_can_upload_attachment()
    {
        $incident = Incident::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 1024);
        
        $response = $this->postJson("/api/v1/incidents/{$incident->id}/attachments", [
            'file' => $file,
            'description' => 'Test document'
        ]);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.filename', 'document.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonPath('data.file_size', 1024 * 1024);
        
        $this->assertDatabaseHas('incident_attachments', [
            'incident_id' => $incident->id,
            'filename' => 'document.pdf'
        ]);
    }

    public function test_can_bulk_update_incidents()
    {
        $incidents = Incident::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'new'
        ]);
        
        $response = $this->postJson('/api/v1/incidents/bulk-update', [
            'incident_ids' => $incidents->pluck('id')->toArray(),
            'updates' => [
                'status' => 'in_progress',
                'assigned_group' => 'Level 2 Support'
            ]
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('updated', 3);
        
        foreach ($incidents as $incident) {
            $this->assertDatabaseHas('incidents', [
                'id' => $incident->id,
                'status' => 'in_progress',
                'assigned_group' => 'Level 2 Support'
            ]);
        }
    }

    public function test_can_export_incidents()
    {
        Incident::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        
        $response = $this->postJson('/api/v1/incidents/export', [
            'format' => 'csv',
            'fields' => ['number', 'title', 'priority', 'status', 'created_at']
        ]);
        
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=incidents_export_' . date('Y-m-d') . '.csv');
    }

    public function test_can_get_incident_metrics()
    {
        // Create test data
        Incident::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'new',
            'created_at' => now()->subDays(1)
        ]);
        
        Incident::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'resolved',
            'created_at' => now()->subDays(2),
            'resolved_at' => now()->subDays(1)
        ]);
        
        $response = $this->getJson('/api/v1/incidents-metrics');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_incidents',
                'open_incidents',
                'resolved_today',
                'average_resolution_time',
                'sla_compliance_rate',
                'incidents_by_priority',
                'incidents_by_category',
                'trend_data'
            ]);
    }
}