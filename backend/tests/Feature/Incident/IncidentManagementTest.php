<?php

namespace Tests\Feature\Incident;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Models\IncidentComment;
use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Incident\Actions\UpdateIncidentAction;
use App\Domains\Incident\Actions\AddIncidentCommentAction;
use App\Domains\Incident\Actions\ResolveIncidentAction;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\DTOs\UpdateIncidentData;
use App\Domains\Incident\DTOs\IncidentCommentData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private User $assignee;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and set context
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'subdomain' => 'test',
        ]);
        
        app()->instance('current_tenant', $this->tenant);
        
        // Create users
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'auth0_id' => 'auth0|test',
            'tenant_id' => $this->tenant->id,
        ]);
        
        $this->assignee = User::create([
            'name' => 'Assignee User',
            'email' => 'assignee@example.com',
            'auth0_id' => 'auth0|assignee',
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Set authenticated user
        $this->actingAs($this->user);
    }

    /** @test */
    public function can_create_incident_with_action()
    {
        $data = new CreateIncidentData(
            title: 'Server Down',
            description: 'Production server is not responding',
            priority: Incident::PRIORITY_CRITICAL,
            impact: Incident::IMPACT_ENTERPRISE,
            urgency: 'urgent',
            category: 'Infrastructure',
            subcategory: 'Server',
            tags: ['production', 'critical'],
            custom_fields: ['affected_users' => 1000]
        );
        
        $action = new CreateIncidentAction();
        $incident = $action->execute($data);
        
        $this->assertInstanceOf(Incident::class, $incident);
        $this->assertEquals('Server Down', $incident->title);
        $this->assertEquals(Incident::STATUS_OPEN, $incident->status);
        $this->assertEquals($this->user->id, $incident->created_by);
        $this->assertNotNull($incident->sla_deadline);
        
        // Check SLA deadline for critical priority (4 hours)
        $expectedDeadline = Carbon::now()->addHours(4);
        $this->assertTrue($incident->sla_deadline->between(
            $expectedDeadline->subMinute(),
            $expectedDeadline->addMinute()
        ));
    }

    /** @test */
    public function can_update_incident_with_action()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'priority' => Incident::PRIORITY_LOW,
            'impact' => Incident::IMPACT_LOW,
            'urgency' => 'low',
            'category' => 'Hardware',
            'created_by' => $this->user->id,
        ]);
        
        $data = new UpdateIncidentData(
            title: 'Updated Title',
            priority: Incident::PRIORITY_HIGH,
            assigned_to: $this->assignee->id,
            status: Incident::STATUS_IN_PROGRESS
        );
        
        $action = new UpdateIncidentAction();
        $updatedIncident = $action->execute($incident, $data);
        
        $this->assertEquals('Updated Title', $updatedIncident->title);
        $this->assertEquals(Incident::PRIORITY_HIGH, $updatedIncident->priority);
        $this->assertEquals($this->assignee->id, $updatedIncident->assigned_to);
        $this->assertEquals(Incident::STATUS_IN_PROGRESS, $updatedIncident->status);
        
        // Check history was recorded
        $history = $updatedIncident->history;
        $this->assertCount(4, $history); // 4 fields were updated
    }

    /** @test */
    public function can_add_comment_to_incident()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Test Incident',
            'description' => 'Test description',
            'priority' => Incident::PRIORITY_MEDIUM,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'medium',
            'category' => 'Software',
            'created_by' => $this->user->id,
        ]);
        
        $data = new IncidentCommentData(
            comment: 'I am investigating this issue',
            is_internal: false,
            mentioned_users: [$this->assignee->id]
        );
        
        $action = new AddIncidentCommentAction();
        $comment = $action->execute($incident, $data);
        
        $this->assertInstanceOf(IncidentComment::class, $comment);
        $this->assertEquals('I am investigating this issue', $comment->comment);
        $this->assertEquals($this->user->id, $comment->user_id);
        $this->assertContains($this->assignee->id, $comment->mentioned_users);
    }

    /** @test */
    public function can_resolve_incident()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Problem to Resolve',
            'description' => 'This needs resolution',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'Network',
            'created_by' => $this->user->id,
            'status' => Incident::STATUS_IN_PROGRESS,
        ]);
        
        $resolutionNotes = 'Fixed by restarting the network service';
        
        $action = new ResolveIncidentAction();
        $resolvedIncident = $action->execute($incident, $resolutionNotes);
        
        $this->assertEquals(Incident::STATUS_RESOLVED, $resolvedIncident->status);
        $this->assertEquals($resolutionNotes, $resolvedIncident->resolution_notes);
        $this->assertNotNull($resolvedIncident->resolved_at);
        $this->assertTrue($resolvedIncident->isResolved());
    }

    /** @test */
    public function sla_calculation_based_on_priority()
    {
        $priorities = [
            Incident::PRIORITY_CRITICAL => 4,
            Incident::PRIORITY_HIGH => 8,
            Incident::PRIORITY_MEDIUM => 24,
            Incident::PRIORITY_LOW => 72,
        ];
        
        foreach ($priorities as $priority => $expectedHours) {
            $incident = new Incident(['priority' => $priority]);
            $this->assertEquals($expectedHours, $incident->getSlaHours());
        }
    }

    /** @test */
    public function can_detect_overdue_incidents()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Overdue Incident',
            'description' => 'This is overdue',
            'priority' => Incident::PRIORITY_HIGH,
            'impact' => Incident::IMPACT_HIGH,
            'urgency' => 'high',
            'category' => 'Security',
            'created_by' => $this->user->id,
            'sla_deadline' => Carbon::now()->subHour(), // 1 hour ago
            'status' => Incident::STATUS_OPEN,
        ]);
        
        $this->assertTrue($incident->isOverdue());
        
        // Resolved incidents should not be overdue
        $incident->status = Incident::STATUS_RESOLVED;
        $incident->save();
        
        $this->assertFalse($incident->isOverdue());
    }

    /** @test */
    public function incident_relationships_work_correctly()
    {
        $incident = Incident::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Relationship Test',
            'description' => 'Testing relationships',
            'priority' => Incident::PRIORITY_MEDIUM,
            'impact' => Incident::IMPACT_MEDIUM,
            'urgency' => 'medium',
            'category' => 'Other',
            'created_by' => $this->user->id,
            'assigned_to' => $this->assignee->id,
        ]);
        
        // Add comment
        $comment = IncidentComment::create([
            'tenant_id' => $this->tenant->id,
            'incident_id' => $incident->id,
            'user_id' => $this->user->id,
            'comment' => 'Test comment',
        ]);
        
        // Test relationships
        $this->assertEquals($this->user->id, $incident->creator->id);
        $this->assertEquals($this->assignee->id, $incident->assignee->id);
        $this->assertCount(1, $incident->comments);
        $this->assertEquals($comment->id, $incident->comments->first()->id);
    }
}