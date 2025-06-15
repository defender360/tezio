<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceCatalog;
use App\Http\Requests\ServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    /**
     * Display a listing of service requests.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceRequest::with(['requester', 'assignee', 'service', 'tasks', 'approvals']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->has('requester_id')) {
                $query->where('requester_id', $request->requester_id);
            }

            if ($request->has('assignee_id')) {
                $query->where('assignee_id', $request->assignee_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('request_number', 'like', "%{$search}%");
                });
            }

            // My requests filter
            if ($request->has('my_requests') && $request->my_requests) {
                $query->where('requester_id', Auth::id());
            }

            // Assigned to me filter
            if ($request->has('assigned_to_me') && $request->assigned_to_me) {
                $query->where('assignee_id', Auth::id());
            }

            $requests = $query->orderBy('created_at', 'desc')
                            ->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => ServiceRequestResource::collection($requests),
                'meta' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching service requests: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch service requests'
            ], 500);
        }
    }

    /**
     * Get service catalog.
     */
    public function catalog(Request $request): JsonResponse
    {
        try {
            $query = ServiceCatalog::where('is_active', true);

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $services = $query->orderBy('popularity', 'desc')
                            ->orderBy('name')
                            ->get();

            $categories = ServiceCatalog::where('is_active', true)
                                      ->distinct()
                                      ->pluck('category');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'services' => $services,
                    'categories' => $categories
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching service catalog: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch service catalog'
            ], 500);
        }
    }

    /**
     * Store a newly created service request.
     */
    public function store(ServiceRequestRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['requester_id'] = Auth::id();
            $data['request_number'] = $this->generateRequestNumber();
            $data['status'] = 'submitted';

            // Get service details
            $service = ServiceCatalog::findOrFail($data['service_id']);
            $data['sla_response_due'] = now()->addHours($service->sla_response_hours);
            $data['sla_resolution_due'] = now()->addHours($service->sla_resolution_hours);

            $serviceRequest = ServiceRequest::create($data);

            // Create initial task if service has a workflow
            if ($service->workflow_template) {
                $this->createTasksFromTemplate($serviceRequest, $service->workflow_template);
            }

            // Check if approval is required
            if ($service->requires_approval) {
                $this->createApprovalRequest($serviceRequest);
                $serviceRequest->update(['status' => 'pending_approval']);
            }

            // Update service popularity
            $service->increment('popularity');

            DB::commit();

            // Send notifications
            // $this->notifyServiceDesk($serviceRequest);

            return response()->json([
                'status' => 'success',
                'message' => 'Service request created successfully',
                'data' => new ServiceRequestResource($serviceRequest->load(['requester', 'service', 'tasks']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service request'
            ], 500);
        }
    }

    /**
     * Display the specified service request.
     */
    public function show(ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            // Check access rights
            if (!$this->canViewRequest($serviceRequest)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to view this request'
                ], 403);
            }

            $serviceRequest->load(['requester', 'assignee', 'service', 'tasks', 'approvals', 'activities', 'attachments']);

            return response()->json([
                'status' => 'success',
                'data' => new ServiceRequestResource($serviceRequest)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch service request'
            ], 500);
        }
    }

    /**
     * Update the specified service request.
     */
    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if user can update this request
            if (!$this->canUpdateRequest($serviceRequest)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this request'
                ], 403);
            }

            $oldStatus = $serviceRequest->status;
            $serviceRequest->update($request->validated());

            // Log status change
            if ($oldStatus !== $serviceRequest->status) {
                $this->logActivity($serviceRequest, 'status_changed', "Status changed from {$oldStatus} to {$serviceRequest->status}");
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service request updated successfully',
                'data' => new ServiceRequestResource($serviceRequest->fresh(['requester', 'assignee', 'service']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service request'
            ], 500);
        }
    }

    /**
     * Remove the specified service request.
     */
    public function destroy(ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            // Only allow deletion of submitted/cancelled requests
            if (!in_array($serviceRequest->status, ['submitted', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only submitted or cancelled requests can be deleted'
                ], 400);
            }

            // Check if user can delete
            if ($serviceRequest->requester_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this request'
                ], 403);
            }

            $serviceRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service request deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service request'
            ], 500);
        }
    }

    /**
     * Approve service request.
     */
    public function approve(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($serviceRequest->status !== 'pending_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request is not pending approval'
                ], 400);
            }

            // Check if user can approve
            $approval = $serviceRequest->approvals()
                ->where('approver_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to approve this request'
                ], 403);
            }

            // Update approval
            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
                'comments' => $request->comments
            ]);

            // Check if all approvals are complete
            $pendingApprovals = $serviceRequest->approvals()
                ->where('status', 'pending')
                ->count();

            if ($pendingApprovals === 0) {
                $serviceRequest->update([
                    'status' => 'approved',
                    'approved_at' => now()
                ]);

                // Auto-assign if configured
                $this->autoAssignRequest($serviceRequest);
            }

            // Log activity
            $this->logActivity($serviceRequest, 'approved', 'Request approved', $request->comments);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service request approved successfully',
                'data' => new ServiceRequestResource($serviceRequest->fresh(['approvals']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error approving service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve service request'
            ], 500);
        }
    }

    /**
     * Reject service request.
     */
    public function reject(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($serviceRequest->status !== 'pending_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request is not pending approval'
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            // Check if user can reject
            $approval = $serviceRequest->approvals()
                ->where('approver_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to reject this request'
                ], 403);
            }

            // Update approval
            $approval->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'comments' => $request->reason
            ]);

            // Update request status
            $serviceRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $request->reason
            ]);

            // Log activity
            $this->logActivity($serviceRequest, 'rejected', 'Request rejected', $request->reason);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service request rejected',
                'data' => new ServiceRequestResource($serviceRequest->fresh(['approvals']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error rejecting service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject service request'
            ], 500);
        }
    }

    /**
     * Assign service request.
     */
    public function assign(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            $request->validate([
                'assignee_id' => 'required|exists:users,id'
            ]);

            $oldAssignee = $serviceRequest->assignee_id;

            $serviceRequest->update([
                'assignee_id' => $request->assignee_id,
                'assigned_at' => now(),
                'status' => 'assigned'
            ]);

            // Log activity
            $this->logActivity($serviceRequest, 'assigned', "Request assigned to user {$request->assignee_id}");

            // Notify new assignee
            // $this->notifyAssignee($serviceRequest);

            return response()->json([
                'status' => 'success',
                'message' => 'Service request assigned successfully',
                'data' => new ServiceRequestResource($serviceRequest->fresh(['assignee']))
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign service request'
            ], 500);
        }
    }

    /**
     * Start working on service request.
     */
    public function start(ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            if (!in_array($serviceRequest->status, ['approved', 'assigned'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request must be approved or assigned to start work'
                ], 400);
            }

            if ($serviceRequest->assignee_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You must be assigned to this request to start work'
                ], 403);
            }

            $serviceRequest->update([
                'status' => 'in_progress',
                'work_started_at' => now()
            ]);

            // Log activity
            $this->logActivity($serviceRequest, 'work_started', 'Work started on request');

            return response()->json([
                'status' => 'success',
                'message' => 'Work started on service request',
                'data' => new ServiceRequestResource($serviceRequest)
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start service request'
            ], 500);
        }
    }

    /**
     * Complete service request.
     */
    public function complete(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($serviceRequest->status !== 'in_progress') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request must be in progress to complete'
                ], 400);
            }

            if ($serviceRequest->assignee_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You must be assigned to this request to complete it'
                ], 403);
            }

            $request->validate([
                'resolution_notes' => 'required|string'
            ]);

            // Check if all tasks are completed
            $incompleteTasks = $serviceRequest->tasks()
                ->where('status', '!=', 'completed')
                ->count();

            if ($incompleteTasks > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'All tasks must be completed before completing the request'
                ], 400);
            }

            $serviceRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'resolution_notes' => $request->resolution_notes,
                'actual_effort_hours' => $request->actual_effort_hours ?? null
            ]);

            // Check SLA compliance
            $this->checkSlaCompliance($serviceRequest);

            // Log activity
            $this->logActivity($serviceRequest, 'completed', 'Request completed', $request->resolution_notes);

            DB::commit();

            // Send satisfaction survey
            // $this->sendSatisfactionSurvey($serviceRequest);

            return response()->json([
                'status' => 'success',
                'message' => 'Service request completed successfully',
                'data' => new ServiceRequestResource($serviceRequest)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error completing service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete service request'
            ], 500);
        }
    }

    /**
     * Cancel service request.
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            if (in_array($serviceRequest->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel a completed or already cancelled request'
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $serviceRequest->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reason,
                'cancelled_by' => Auth::id()
            ]);

            // Cancel all pending tasks
            $serviceRequest->tasks()
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['status' => 'cancelled']);

            // Log activity
            $this->logActivity($serviceRequest, 'cancelled', 'Request cancelled', $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Service request cancelled',
                'data' => new ServiceRequestResource($serviceRequest)
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling service request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel service request'
            ], 500);
        }
    }

    /**
     * Get tasks for service request.
     */
    public function tasks(ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            $tasks = $serviceRequest->tasks()
                ->with(['assignee', 'creator'])
                ->orderBy('sequence')
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tasks: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks'
            ], 500);
        }
    }

    /**
     * Create task for service request.
     */
    public function createTask(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'assignee_id' => 'nullable|exists:users,id',
                'due_date' => 'nullable|date',
                'priority' => 'nullable|in:low,medium,high,urgent'
            ]);

            $task = $serviceRequest->tasks()->create([
                'title' => $request->title,
                'description' => $request->description,
                'assignee_id' => $request->assignee_id,
                'creator_id' => Auth::id(),
                'due_date' => $request->due_date,
                'priority' => $request->priority ?? 'medium',
                'status' => 'pending'
            ]);

            // Log activity
            $this->logActivity($serviceRequest, 'task_created', "Task created: {$task->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => $task->load(['assignee', 'creator'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating task: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create task'
            ], 500);
        }
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(Request $request, ServiceRequest $serviceRequest, $taskId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'notes' => 'nullable|string'
            ]);

            $task = $serviceRequest->tasks()->findOrFail($taskId);

            // Check if user can update task
            if ($task->assignee_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'service_desk'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this task'
                ], 403);
            }

            $oldStatus = $task->status;
            $task->update([
                'status' => $request->status,
                'completed_at' => $request->status === 'completed' ? now() : null,
                'notes' => $request->notes
            ]);

            // Log activity
            $this->logActivity($serviceRequest, 'task_updated', "Task '{$task->title}' status changed from {$oldStatus} to {$request->status}");

            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating task: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task'
            ], 500);
        }
    }

    /**
     * Get SLA tracking information.
     */
    public function slaTracking(ServiceRequest $serviceRequest): JsonResponse
    {
        try {
            $now = now();
            $responseTime = $serviceRequest->assigned_at 
                ? $serviceRequest->assigned_at->diffInMinutes($serviceRequest->created_at) 
                : $now->diffInMinutes($serviceRequest->created_at);

            $resolutionTime = $serviceRequest->completed_at 
                ? $serviceRequest->completed_at->diffInMinutes($serviceRequest->created_at)
                : $now->diffInMinutes($serviceRequest->created_at);

            $slaData = [
                'response_sla' => [
                    'target' => $serviceRequest->sla_response_due,
                    'actual' => $serviceRequest->assigned_at,
                    'time_taken_minutes' => $responseTime,
                    'is_breached' => $serviceRequest->assigned_at 
                        ? $serviceRequest->assigned_at->isAfter($serviceRequest->sla_response_due)
                        : $now->isAfter($serviceRequest->sla_response_due),
                    'remaining_time' => $serviceRequest->assigned_at 
                        ? null 
                        : $serviceRequest->sla_response_due->diffForHumans()
                ],
                'resolution_sla' => [
                    'target' => $serviceRequest->sla_resolution_due,
                    'actual' => $serviceRequest->completed_at,
                    'time_taken_minutes' => $resolutionTime,
                    'is_breached' => $serviceRequest->completed_at 
                        ? $serviceRequest->completed_at->isAfter($serviceRequest->sla_resolution_due)
                        : $now->isAfter($serviceRequest->sla_resolution_due),
                    'remaining_time' => $serviceRequest->completed_at 
                        ? null 
                        : $serviceRequest->sla_resolution_due->diffForHumans()
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $slaData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting SLA tracking: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get SLA tracking'
            ], 500);
        }
    }

    /**
     * Generate unique request number.
     */
    private function generateRequestNumber(): string
    {
        $prefix = 'SR';
        $date = now()->format('Ymd');
        $lastRequest = ServiceRequest::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRequest ? intval(substr($lastRequest->request_number, -4)) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Check if user can view request.
     */
    private function canViewRequest(ServiceRequest $request): bool
    {
        $user = Auth::user();
        
        if ($user->id === $request->requester_id) {
            return true;
        }

        if ($user->id === $request->assignee_id) {
            return true;
        }

        if ($user->hasRole(['admin', 'service_desk'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update request.
     */
    private function canUpdateRequest(ServiceRequest $request): bool
    {
        $user = Auth::user();
        
        if ($user->id === $request->assignee_id) {
            return true;
        }

        if ($user->hasRole(['admin', 'service_desk'])) {
            return true;
        }

        return false;
    }

    /**
     * Create tasks from workflow template.
     */
    private function createTasksFromTemplate(ServiceRequest $request, array $template): void
    {
        foreach ($template['tasks'] as $index => $taskTemplate) {
            $request->tasks()->create([
                'title' => $taskTemplate['title'],
                'description' => $taskTemplate['description'] ?? null,
                'assignee_id' => $taskTemplate['default_assignee_id'] ?? null,
                'sequence' => $index + 1,
                'estimated_hours' => $taskTemplate['estimated_hours'] ?? null,
                'status' => 'pending',
                'creator_id' => Auth::id()
            ]);
        }
    }

    /**
     * Create approval request.
     */
    private function createApprovalRequest(ServiceRequest $request): void
    {
        $service = $request->service;
        $approvers = $service->approval_config['approvers'] ?? [];

        foreach ($approvers as $approverId) {
            $request->approvals()->create([
                'approver_id' => $approverId,
                'status' => 'pending',
                'requested_at' => now()
            ]);
        }
    }

    /**
     * Auto-assign request based on rules.
     */
    private function autoAssignRequest(ServiceRequest $request): void
    {
        // Implement auto-assignment logic based on service configuration
        // This is a simplified version
        $service = $request->service;
        
        if ($service->default_assignee_id) {
            $request->update([
                'assignee_id' => $service->default_assignee_id,
                'assigned_at' => now(),
                'status' => 'assigned'
            ]);
        }
    }

    /**
     * Check SLA compliance.
     */
    private function checkSlaCompliance(ServiceRequest $request): void
    {
        $request->update([
            'sla_response_met' => $request->assigned_at && $request->assigned_at->lte($request->sla_response_due),
            'sla_resolution_met' => $request->completed_at && $request->completed_at->lte($request->sla_resolution_due)
        ]);
    }

    /**
     * Log activity for service request.
     */
    private function logActivity(ServiceRequest $request, string $action, string $description, ?string $notes = null): void
    {
        $request->activities()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'notes' => $notes,
            'performed_at' => now()
        ]);
    }
}