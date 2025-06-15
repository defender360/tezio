<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Change;
use App\Http\Requests\ChangeRequest;
use App\Http\Resources\ChangeResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChangeController extends Controller
{
    /**
     * Display a listing of changes.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Change::with(['requester', 'approver', 'implementer', 'cab_members', 'related_incidents']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('date_from')) {
                $query->where('scheduled_start', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('scheduled_start', '<=', $request->date_to);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('change_number', 'like', "%{$search}%");
                });
            }

            $changes = $query->orderBy('created_at', 'desc')
                           ->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => ChangeResource::collection($changes),
                'meta' => [
                    'current_page' => $changes->currentPage(),
                    'last_page' => $changes->lastPage(),
                    'per_page' => $changes->perPage(),
                    'total' => $changes->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching changes: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch changes'
            ], 500);
        }
    }

    /**
     * Store a newly created change.
     */
    public function store(ChangeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['requester_id'] = Auth::id();
            $data['change_number'] = $this->generateChangeNumber();
            $data['status'] = 'draft';

            $change = Change::create($data);

            // Attach related incidents if provided
            if ($request->has('incident_ids')) {
                $change->related_incidents()->attach($request->incident_ids);
            }

            // Attach CAB members if provided
            if ($request->has('cab_member_ids')) {
                $change->cab_members()->attach($request->cab_member_ids);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Change created successfully',
                'data' => new ChangeResource($change->load(['requester', 'related_incidents', 'cab_members']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create change'
            ], 500);
        }
    }

    /**
     * Display the specified change.
     */
    public function show(Change $change): JsonResponse
    {
        try {
            $change->load(['requester', 'approver', 'implementer', 'cab_members', 'related_incidents', 'activities']);

            return response()->json([
                'status' => 'success',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch change'
            ], 500);
        }
    }

    /**
     * Update the specified change.
     */
    public function update(ChangeRequest $request, Change $change): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if user can update this change
            if (!$this->canUpdateChange($change)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this change'
                ], 403);
            }

            $change->update($request->validated());

            // Update related incidents
            if ($request->has('incident_ids')) {
                $change->related_incidents()->sync($request->incident_ids);
            }

            // Update CAB members
            if ($request->has('cab_member_ids')) {
                $change->cab_members()->sync($request->cab_member_ids);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Change updated successfully',
                'data' => new ChangeResource($change->fresh(['requester', 'related_incidents', 'cab_members']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update change'
            ], 500);
        }
    }

    /**
     * Remove the specified change.
     */
    public function destroy(Change $change): JsonResponse
    {
        try {
            // Only allow deletion of draft changes
            if ($change->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft changes can be deleted'
                ], 400);
            }

            $change->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Change deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete change'
            ], 500);
        }
    }

    /**
     * Submit change for approval.
     */
    public function submit(Change $change): JsonResponse
    {
        try {
            if ($change->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft changes can be submitted'
                ], 400);
            }

            $change->update([
                'status' => 'pending_approval',
                'submitted_at' => now()
            ]);

            // Log activity
            $this->logActivity($change, 'submitted', 'Change submitted for approval');

            // Send notifications to approvers
            // $this->notifyApprovers($change);

            return response()->json([
                'status' => 'success',
                'message' => 'Change submitted for approval',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error submitting change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit change'
            ], 500);
        }
    }

    /**
     * Approve change.
     */
    public function approve(Request $request, Change $change): JsonResponse
    {
        try {
            if ($change->status !== 'pending_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Change is not pending approval'
                ], 400);
            }

            // Check if user has approval rights
            if (!$this->canApproveChange($change)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to approve this change'
                ], 403);
            }

            $change->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->notes
            ]);

            // Log activity
            $this->logActivity($change, 'approved', 'Change approved', $request->notes);

            return response()->json([
                'status' => 'success',
                'message' => 'Change approved successfully',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve change'
            ], 500);
        }
    }

    /**
     * Reject change.
     */
    public function reject(Request $request, Change $change): JsonResponse
    {
        try {
            if ($change->status !== 'pending_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Change is not pending approval'
                ], 400);
            }

            // Check if user has approval rights
            if (!$this->canApproveChange($change)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to reject this change'
                ], 403);
            }

            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $change->update([
                'status' => 'rejected',
                'approver_id' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->reason
            ]);

            // Log activity
            $this->logActivity($change, 'rejected', 'Change rejected', $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Change rejected',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject change'
            ], 500);
        }
    }

    /**
     * Implement change.
     */
    public function implement(Request $request, Change $change): JsonResponse
    {
        try {
            if ($change->status !== 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only approved changes can be implemented'
                ], 400);
            }

            $change->update([
                'status' => 'implementing',
                'implementer_id' => Auth::id(),
                'implementation_started_at' => now()
            ]);

            // Log activity
            $this->logActivity($change, 'implementation_started', 'Change implementation started');

            return response()->json([
                'status' => 'success',
                'message' => 'Change implementation started',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error implementing change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start implementation'
            ], 500);
        }
    }

    /**
     * Complete change implementation.
     */
    public function complete(Request $request, Change $change): JsonResponse
    {
        try {
            if ($change->status !== 'implementing') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Change is not in implementation phase'
                ], 400);
            }

            $request->validate([
                'notes' => 'nullable|string',
                'success' => 'required|boolean'
            ]);

            $status = $request->success ? 'completed' : 'failed';

            $change->update([
                'status' => $status,
                'implementation_completed_at' => now(),
                'implementation_notes' => $request->notes
            ]);

            // Log activity
            $this->logActivity($change, 'implementation_completed', "Change implementation {$status}", $request->notes);

            return response()->json([
                'status' => 'success',
                'message' => "Change implementation {$status}",
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing change: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete change'
            ], 500);
        }
    }

    /**
     * Schedule CAB meeting.
     */
    public function scheduleCabMeeting(Request $request, Change $change): JsonResponse
    {
        try {
            $request->validate([
                'meeting_date' => 'required|date|after:now',
                'meeting_location' => 'nullable|string',
                'meeting_link' => 'nullable|url',
                'agenda' => 'nullable|string'
            ]);

            $change->update([
                'cab_meeting_date' => $request->meeting_date,
                'cab_meeting_location' => $request->meeting_location,
                'cab_meeting_link' => $request->meeting_link,
                'cab_meeting_agenda' => $request->agenda
            ]);

            // Notify CAB members
            // $this->notifyCabMembers($change);

            // Log activity
            $this->logActivity($change, 'cab_scheduled', 'CAB meeting scheduled for ' . $request->meeting_date);

            return response()->json([
                'status' => 'success',
                'message' => 'CAB meeting scheduled successfully',
                'data' => new ChangeResource($change)
            ]);
        } catch (\Exception $e) {
            Log::error('Error scheduling CAB meeting: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to schedule CAB meeting'
            ], 500);
        }
    }

    /**
     * Get impact analysis for change.
     */
    public function impactAnalysis(Change $change): JsonResponse
    {
        try {
            $impactData = [
                'affected_services' => $change->affected_services ?? [],
                'affected_users' => $change->affected_users_count ?? 0,
                'downtime_duration' => $change->estimated_downtime ?? 0,
                'risk_level' => $change->risk_level ?? 'medium',
                'business_impact' => $change->business_impact ?? 'moderate',
                'technical_complexity' => $change->technical_complexity ?? 'medium',
                'rollback_plan' => $change->rollback_plan ?? null,
                'dependencies' => $change->dependencies ?? [],
                'related_changes' => Change::where('id', '!=', $change->id)
                    ->where('scheduled_start', '>=', $change->scheduled_start)
                    ->where('scheduled_end', '<=', $change->scheduled_end)
                    ->get(['id', 'change_number', 'title', 'status'])
            ];

            return response()->json([
                'status' => 'success',
                'data' => $impactData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting impact analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get impact analysis'
            ], 500);
        }
    }

    /**
     * Get change calendar view.
     */
    public function calendar(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date ?? Carbon::now()->startOfMonth();
            $endDate = $request->end_date ?? Carbon::now()->endOfMonth();

            $changes = Change::whereBetween('scheduled_start', [$startDate, $endDate])
                ->orWhereBetween('scheduled_end', [$startDate, $endDate])
                ->get(['id', 'change_number', 'title', 'type', 'status', 'priority', 'scheduled_start', 'scheduled_end']);

            $calendarData = $changes->map(function ($change) {
                return [
                    'id' => $change->id,
                    'title' => $change->change_number . ': ' . $change->title,
                    'start' => $change->scheduled_start,
                    'end' => $change->scheduled_end,
                    'color' => $this->getChangeColor($change),
                    'extendedProps' => [
                        'change_number' => $change->change_number,
                        'type' => $change->type,
                        'status' => $change->status,
                        'priority' => $change->priority
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $calendarData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting change calendar: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get change calendar'
            ], 500);
        }
    }

    /**
     * Perform risk assessment.
     */
    public function riskAssessment(Request $request, Change $change): JsonResponse
    {
        try {
            $request->validate([
                'probability' => 'required|integer|between:1,5',
                'impact' => 'required|integer|between:1,5',
                'mitigation_plan' => 'nullable|string',
                'contingency_plan' => 'nullable|string'
            ]);

            $riskScore = $request->probability * $request->impact;
            $riskLevel = $this->calculateRiskLevel($riskScore);

            $change->update([
                'risk_probability' => $request->probability,
                'risk_impact' => $request->impact,
                'risk_score' => $riskScore,
                'risk_level' => $riskLevel,
                'risk_mitigation_plan' => $request->mitigation_plan,
                'risk_contingency_plan' => $request->contingency_plan,
                'risk_assessed_at' => now(),
                'risk_assessed_by' => Auth::id()
            ]);

            // Log activity
            $this->logActivity($change, 'risk_assessed', "Risk assessment completed. Risk level: {$riskLevel}");

            return response()->json([
                'status' => 'success',
                'message' => 'Risk assessment completed',
                'data' => [
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'change' => new ChangeResource($change)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error performing risk assessment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform risk assessment'
            ], 500);
        }
    }

    /**
     * Generate unique change number.
     */
    private function generateChangeNumber(): string
    {
        $prefix = 'CHG';
        $date = now()->format('Ymd');
        $lastChange = Change::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastChange ? intval(substr($lastChange->change_number, -4)) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Check if user can update change.
     */
    private function canUpdateChange(Change $change): bool
    {
        $user = Auth::user();
        
        // Check various conditions
        if ($user->id === $change->requester_id) {
            return true;
        }

        if ($user->hasRole(['admin', 'change_manager'])) {
            return true;
        }

        if ($change->status === 'implementing' && $user->id === $change->implementer_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can approve change.
     */
    private function canApproveChange(Change $change): bool
    {
        $user = Auth::user();
        
        // User cannot approve their own change
        if ($user->id === $change->requester_id) {
            return false;
        }

        // Check if user has approval rights
        if ($user->hasRole(['admin', 'change_manager', 'approver'])) {
            return true;
        }

        // Check if user is in CAB
        if ($change->cab_members->contains($user->id)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate risk level based on score.
     */
    private function calculateRiskLevel(int $score): string
    {
        if ($score <= 5) return 'low';
        if ($score <= 10) return 'medium';
        if ($score <= 15) return 'high';
        return 'critical';
    }

    /**
     * Get color for change based on status/priority.
     */
    private function getChangeColor(Change $change): string
    {
        $colors = [
            'emergency' => '#ff0000',
            'high' => '#ff6600',
            'medium' => '#ffcc00',
            'low' => '#00cc00'
        ];

        return $colors[$change->priority] ?? '#0066cc';
    }

    /**
     * Log activity for change.
     */
    private function logActivity(Change $change, string $action, string $description, ?string $notes = null): void
    {
        $change->activities()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'notes' => $notes,
            'performed_at' => now()
        ]);
    }
}