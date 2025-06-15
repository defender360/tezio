<?php

namespace Tests\Performance;

use App\Models\Incident;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncidentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Disable query logging for performance tests
        DB::disableQueryLog();
    }

    public function test_can_handle_large_incident_list()
    {
        // Create 10,000 incidents
        $chunks = 100;
        $perChunk = 100;
        
        for ($i = 0; $i < $chunks; $i++) {
            $incidents = Incident::factory()->count($perChunk)->make([
                'tenant_id' => $this->tenant->id,
                'reporter_id' => $this->user->id
            ])->toArray();
            
            Incident::insert($incidents);
        }
        
        $this->actingAs($this->user);
        
        // Measure response time
        $start = microtime(true);
        
        $response = $this->getJson('/api/v1/incidents?per_page=50');
        
        $duration = microtime(true) - $start;
        
        $response->assertStatus(200);
        $this->assertLessThan(1.0, $duration, 'Response took longer than 1 second');
        
        // Verify pagination is working
        $this->assertEquals(50, count($response->json('data')));
        $this->assertEquals(10000, $response->json('meta.total'));
    }

    public function test_search_performance_with_large_dataset()
    {
        // Create incidents with various titles
        $titles = [
            'Email server down',
            'Database connection timeout',
            'Application crash',
            'Network connectivity issue',
            'Printer not working',
            'Password reset request',
            'Software installation',
            'VPN access problem',
            'File share permissions',
            'System update required'
        ];
        
        // Create 5000 incidents with random titles
        for ($i = 0; $i < 500; $i++) {
            $batch = [];
            for ($j = 0; $j < 10; $j++) {
                $batch[] = [
                    'number' => 'INC-' . str_pad($i * 10 + $j, 6, '0', STR_PAD_LEFT),
                    'title' => $titles[array_rand($titles)] . ' - ' . uniqid(),
                    'description' => 'Description for incident',
                    'priority' => ['low', 'medium', 'high', 'critical'][rand(0, 3)],
                    'status' => ['new', 'in_progress', 'resolved', 'closed'][rand(0, 3)],
                    'impact' => ['low', 'medium', 'high'][rand(0, 2)],
                    'urgency' => ['low', 'medium', 'high'][rand(0, 2)],
                    'tenant_id' => $this->tenant->id,
                    'reporter_id' => $this->user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            Incident::insert($batch);
        }
        
        $this->actingAs($this->user);
        
        // Test search performance
        $start = microtime(true);
        
        $response = $this->getJson('/api/v1/incidents?search=email');
        
        $duration = microtime(true) - $start;
        
        $response->assertStatus(200);
        $this->assertLessThan(0.5, $duration, 'Search took longer than 0.5 seconds');
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_bulk_update_performance()
    {
        // Create 1000 incidents
        $incidentIds = [];
        for ($i = 0; $i < 10; $i++) {
            $batch = Incident::factory()->count(100)->create([
                'tenant_id' => $this->tenant->id,
                'status' => 'new'
            ]);
            $incidentIds = array_merge($incidentIds, $batch->pluck('id')->toArray());
        }
        
        $this->actingAs($this->user);
        
        // Measure bulk update performance
        $start = microtime(true);
        
        $response = $this->postJson('/api/v1/incidents/bulk-update', [
            'incident_ids' => $incidentIds,
            'updates' => [
                'status' => 'in_progress',
                'assigned_group' => 'IT Support'
            ]
        ]);
        
        $duration = microtime(true) - $start;
        
        $response->assertStatus(200);
        $this->assertLessThan(5.0, $duration, 'Bulk update took longer than 5 seconds');
        $this->assertEquals(1000, $response->json('updated'));
    }

    public function test_concurrent_incident_creation()
    {
        $this->actingAs($this->user);
        
        $concurrentRequests = 50;
        $promises = [];
        
        $start = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $data = [
                'title' => "Concurrent incident {$i}",
                'description' => 'Testing concurrent creation',
                'impact' => 'medium',
                'urgency' => 'medium',
                'category' => 'test'
            ];
            
            $this->postJson('/api/v1/incidents', $data);
        }
        
        $duration = microtime(true) - $start;
        
        // Verify all incidents were created
        $this->assertEquals($concurrentRequests, Incident::where('tenant_id', $this->tenant->id)->count());
        
        // Ensure no duplicate numbers were generated
        $numbers = Incident::where('tenant_id', $this->tenant->id)->pluck('number');
        $this->assertEquals($concurrentRequests, $numbers->unique()->count());
        
        $this->assertLessThan(10.0, $duration, 'Concurrent creation took longer than 10 seconds');
    }

    public function test_analytics_query_performance()
    {
        // Create test data with various dates
        $now = now();
        
        for ($days = 30; $days >= 0; $days--) {
            $date = $now->copy()->subDays($days);
            $count = rand(50, 150);
            
            $incidents = Incident::factory()->count($count)->make([
                'tenant_id' => $this->tenant->id,
                'created_at' => $date,
                'updated_at' => $date
            ])->toArray();
            
            Incident::insert($incidents);
        }
        
        $this->actingAs($this->user);
        
        // Test analytics endpoint performance
        $start = microtime(true);
        
        $response = $this->getJson('/api/v1/analytics/incidents?period=30days');
        
        $duration = microtime(true) - $start;
        
        $response->assertStatus(200);
        $this->assertLessThan(2.0, $duration, 'Analytics query took longer than 2 seconds');
        
        // Verify response structure
        $response->assertJsonStructure([
            'summary' => [
                'total',
                'open',
                'resolved',
                'avg_resolution_time'
            ],
            'trends',
            'by_priority',
            'by_category'
        ]);
    }

    public function test_export_large_dataset_performance()
    {
        // Create 5000 incidents
        for ($i = 0; $i < 50; $i++) {
            $incidents = Incident::factory()->count(100)->make([
                'tenant_id' => $this->tenant->id
            ])->toArray();
            
            Incident::insert($incidents);
        }
        
        $this->actingAs($this->user);
        
        // Test export performance
        $start = microtime(true);
        
        $response = $this->postJson('/api/v1/incidents/export', [
            'format' => 'csv',
            'fields' => ['number', 'title', 'priority', 'status', 'created_at']
        ]);
        
        $duration = microtime(true) - $start;
        
        $response->assertStatus(200);
        $this->assertLessThan(5.0, $duration, 'Export took longer than 5 seconds');
        
        // Verify CSV has correct number of rows (header + 5000 data rows)
        $lines = count(explode("\n", $response->getContent())) - 1; // Subtract empty last line
        $this->assertEquals(5001, $lines);
    }

    public function test_database_query_optimization()
    {
        // Create complex data structure
        $users = User::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);
        
        for ($i = 0; $i < 100; $i++) {
            $incident = Incident::factory()->create([
                'tenant_id' => $this->tenant->id,
                'assigned_to' => $users->random()->id
            ]);
            
            // Add comments
            for ($j = 0; $j < rand(1, 5); $j++) {
                $incident->comments()->create([
                    'content' => 'Comment ' . $j,
                    'user_id' => $users->random()->id
                ]);
            }
            
            // Add attachments
            for ($j = 0; $j < rand(0, 3); $j++) {
                $incident->attachments()->create([
                    'filename' => 'file' . $j . '.pdf',
                    'file_path' => 'path/to/file' . $j . '.pdf',
                    'file_size' => rand(1000, 1000000),
                    'mime_type' => 'application/pdf'
                ]);
            }
        }
        
        $this->actingAs($this->user);
        
        // Enable query log temporarily
        DB::enableQueryLog();
        
        $response = $this->getJson('/api/v1/incidents?include=reporter,assignee,comments,attachments');
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        $response->assertStatus(200);
        
        // Ensure we're not having N+1 query problems
        // Should have: 1 for incidents, 1 for users, 1 for comments, 1 for attachments
        $this->assertLessThanOrEqual(10, count($queries), 'Too many queries executed (possible N+1 problem)');
    }
}