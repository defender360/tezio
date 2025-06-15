<?php

namespace Tests\Feature\MultiTenancy;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create two tenants
        $this->tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'subdomain' => 'tenant1',
        ]);
        
        $this->tenant2 = Tenant::create([
            'name' => 'Tenant 2', 
            'subdomain' => 'tenant2',
        ]);
        
        // Create users for each tenant
        $this->user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@tenant1.com',
            'auth0_id' => 'auth0|user1',
            'tenant_id' => $this->tenant1->id,
        ]);
        
        $this->user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@tenant2.com',
            'auth0_id' => 'auth0|user2',
            'tenant_id' => $this->tenant2->id,
        ]);
    }

    /** @test */
    public function users_can_only_see_their_own_tenant_data()
    {
        // Set tenant 1 context
        app()->instance('current_tenant', $this->tenant1);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant1->id]);
        
        // Create incident for tenant 1
        $incident1 = Incident::create([
            'tenant_id' => $this->tenant1->id,
            'title' => 'Tenant 1 Incident',
            'description' => 'Test incident',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'high',
            'category' => 'Hardware',
            'created_by' => $this->user1->id,
        ]);
        
        // Set tenant 2 context
        app()->instance('current_tenant', $this->tenant2);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant2->id]);
        
        // Create incident for tenant 2
        $incident2 = Incident::create([
            'tenant_id' => $this->tenant2->id,
            'title' => 'Tenant 2 Incident',
            'description' => 'Test incident',
            'priority' => Incident::PRIORITY_LOW,
            'impact' => Incident::IMPACT_LOW,
            'urgency' => 'low',
            'category' => 'Software',
            'created_by' => $this->user2->id,
        ]);
        
        // Test: Tenant 1 can only see their incidents
        app()->instance('current_tenant', $this->tenant1);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant1->id]);
        
        $tenant1Incidents = Incident::all();
        $this->assertCount(1, $tenant1Incidents);
        $this->assertEquals($incident1->id, $tenant1Incidents->first()->id);
        
        // Test: Tenant 2 can only see their incidents
        app()->instance('current_tenant', $this->tenant2);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant2->id]);
        
        $tenant2Incidents = Incident::all();
        $this->assertCount(1, $tenant2Incidents);
        $this->assertEquals($incident2->id, $tenant2Incidents->first()->id);
    }

    /** @test */
    public function tenant_id_is_automatically_set_when_creating_models()
    {
        app()->instance('current_tenant', $this->tenant1);
        
        $incident = new Incident([
            'title' => 'Auto Tenant Test',
            'description' => 'Testing automatic tenant assignment',
            'priority' => Incident::PRIORITY_MEDIUM,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'medium',
            'category' => 'Network',
            'created_by' => $this->user1->id,
        ]);
        
        $incident->save();
        
        $this->assertEquals($this->tenant1->id, $incident->tenant_id);
    }

    /** @test */
    public function cannot_access_data_without_tenant_context()
    {
        // Clear tenant context
        app()->forgetInstance('current_tenant');
        DB::statement("SET SESSION app.current_tenant_id = NULL");
        
        // Should return no results without tenant context
        $incidents = Incident::all();
        $this->assertCount(0, $incidents);
    }

    /** @test */
    public function tenant_scoping_works_with_relationships()
    {
        app()->instance('current_tenant', $this->tenant1);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant1->id]);
        
        // Create incidents
        Incident::create([
            'tenant_id' => $this->tenant1->id,
            'title' => 'Test Incident',
            'description' => 'Test',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'Security',
            'created_by' => $this->user1->id,
        ]);
        
        // Test relationship access
        $userIncidents = $this->user1->createdIncidents;
        $this->assertCount(1, $userIncidents);
        
        // Switch tenant and verify no access
        app()->instance('current_tenant', $this->tenant2);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant2->id]);
        
        $userIncidents = $this->user1->createdIncidents;
        $this->assertCount(0, $userIncidents);
    }
}