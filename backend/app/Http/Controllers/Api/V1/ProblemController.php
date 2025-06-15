<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Problem;
use App\Models\Incident;
use App\Http\Requests\ProblemRequest;
use App\Http\Resources\ProblemResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProblemController extends Controller
{
    /**
     * Display a listing of problems.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Problem::with(['assignee', 'reporter', 'incidents', 'known_errors', 'workarounds']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('assignee_id')) {
                $query->where('assignee_id', $request->assignee_id);
            }

            if ($request->has('is_known_error')) {
                $query->where('is_known_error', $request->boolean('is_known_error'));
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('problem_number', 'like', "%{$search}%")
                      ->orWhere('root_cause', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $problems = $query->orderBy('priority_score', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => ProblemResource::collection($problems),
                'meta' => [
                    'current_page' => $problems->currentPage(),
                    'last_page' => $problems->lastPage(),
                    'per_page' => $problems->perPage(),
                    'total' => $problems->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching problems: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch problems'
            ], 500);
        }
    }

    /**
     * Store a newly created problem.
     */
    public function store(ProblemRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['reporter_id'] = Auth::id();
            $data['problem_number'] = $this->generateProblemNumber();
            $data['status'] = 'open';
            $data['priority_score'] = $this->calculatePriorityScore($data);

            $problem = Problem::create($data);

            // Link incidents if provided
            if ($request->has('incident_ids')) {
                $problem->incidents()->attach($request->incident_ids);
                
                // Update incident status to indicate problem investigation
                Incident::whereIn('id', $request->incident_ids)
                    ->update(['has_problem' => true]);
            }

            // Create initial investigation entry
            $this->createInvestigationEntry($problem, 'Problem created and investigation initiated');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Problem created successfully',
                'data' => new ProblemResource($problem->load(['reporter', 'incidents']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating problem: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create problem'
            ], 500);
        }
    }

    /**
     * Display the specified problem.
     */
    public function show(Problem $problem): JsonResponse
    {
        try {
            $problem->load([
                'assignee', 
                'reporter', 
                'incidents', 
                'known_errors', 
                'workarounds',
                'investigations',
                'activities',
                'related_changes'
            ]);

            return response()->json([
                'status' => 'success',
                'data' => new ProblemResource($problem)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching problem: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch problem'
            ], 500);
        }
    }

    /**
     * Update the specified problem.
     */
    public function update(ProblemRequest $request, Problem $problem): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oldStatus = $problem->status;
            $data = $request->validated();
            $data['priority_score'] = $this->calculatePriorityScore($data);

            $problem->update($data);

            // Update incident links
            if ($request->has('incident_ids')) {
                // Remove problem flag from previously linked incidents
                $problem->incidents()->update(['has_problem' => false]);
                
                // Sync new incidents
                $problem->incidents()->sync($request->incident_ids);
                
                // Add problem flag to newly linked incidents
                Incident::whereIn('id', $request->incident_ids)
                    ->update(['has_problem' => true]);
            }

            // Log status change
            if ($oldStatus !== $problem->status) {
                $this->logActivity($problem, 'status_changed', "Status changed from {$oldStatus} to {$problem->status}");
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Problem updated successfully',
                'data' => new ProblemResource($problem->fresh(['assignee', 'incidents']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating problem: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update problem'
            ], 500);
        }
    }

    /**
     * Remove the specified problem.
     */
    public function destroy(Problem $problem): JsonResponse
    {
        try {
            // Only allow deletion of problems without root cause or resolution
            if ($problem->root_cause || $problem->status === 'resolved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete resolved problems or problems with identified root cause'
                ], 400);
            }

            // Remove problem flag from linked incidents
            $problem->incidents()->update(['has_problem' => false]);

            $problem->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Problem deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting problem: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete problem'
            ], 500);
        }
    }

    /**
     * Add investigation update.
     */
    public function addInvestigation(Request $request, Problem $problem): JsonResponse
    {
        try {
            $request->validate([
                'findings' => 'required|string',
                'actions_taken' => 'nullable|string',
                'next_steps' => 'nullable|string',
                'evidence' => 'nullable|array',
                'evidence.*' => 'file|max:10240' // 10MB max per file
            ]);

            $investigation = $this->createInvestigationEntry(
                $problem,
                $request->findings,
                $request->actions_taken,
                $request->next_steps
            );

            // Handle evidence uploads
            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = $file->store('investigations/' . $problem->id, 'public');
                    $investigation->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ]);
                }
            }

            // Update problem status if needed
            if ($problem->status === 'open') {
                $problem->update(['status' => 'investigating']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Investigation update added successfully',
                'data' => $investigation->load('attachments')
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding investigation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add investigation update'
            ], 500);
        }
    }

    /**
     * Set root cause analysis.
     */
    public function setRootCause(Request $request, Problem $problem): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'root_cause' => 'required|string',
                'root_cause_category' => 'required|string',
                'contributing_factors' => 'nullable|array',
                'contributing_factors.*' => 'string',
                'preventive_measures' => 'nullable|array',
                'preventive_measures.*' => 'string'
            ]);

            $problem->update([
                'root_cause' => $request->root_cause,
                'root_cause_category' => $request->root_cause_category,
                'contributing_factors' => $request->contributing_factors,
                'preventive_measures' => $request->preventive_measures,
                'root_cause_identified_at' => now(),
                'root_cause_identified_by' => Auth::id(),
                'status' => 'root_cause_identified'
            ]);

            // Log activity
            $this->logActivity($problem, 'root_cause_identified', 'Root cause analysis completed');

            // Create investigation entry
            $this->createInvestigationEntry(
                $problem,
                'Root cause identified: ' . $request->root_cause,
                null,
                'Implement preventive measures'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Root cause analysis saved successfully',
                'data' => new ProblemResource($problem)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error setting root cause: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save root cause analysis'
            ], 500);
        }
    }

    /**
     * Convert problem to known error.
     */
    public function convertToKnownError(Request $request, Problem $problem): JsonResponse
    {
        DB::beginTransaction();
        try {
            if (!$problem->root_cause) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Root cause must be identified before converting to known error'
                ], 400);
            }

            $request->validate([
                'error_description' => 'required|string',
                'symptoms' => 'required|array',
                'symptoms.*' => 'string',
                'resolution_steps' => 'nullable|array',
                'resolution_steps.*' => 'string'
            ]);

            // Create known error record
            $knownError = $problem->known_errors()->create([
                'error_code' => $this->generateErrorCode(),
                'description' => $request->error_description,
                'symptoms' => $request->symptoms,
                'root_cause' => $problem->root_cause,
                'resolution_steps' => $request->resolution_steps,
                'created_by' => Auth::id(),
                'is_active' => true
            ]);

            // Update problem
            $problem->update([
                'is_known_error' => true,
                'known_error_id' => $knownError->id
            ]);

            // Log activity
            $this->logActivity($problem, 'converted_to_known_error', 'Problem converted to known error: ' . $knownError->error_code);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Problem converted to known error successfully',
                'data' => [
                    'problem' => new ProblemResource($problem),
                    'known_error' => $knownError
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error converting to known error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert to known error'
            ], 500);
        }
    }

    /**
     * Add workaround.
     */
    public function addWorkaround(Request $request, Problem $problem): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'steps' => 'required|array',
                'steps.*' => 'string',
                'effectiveness' => 'required|in:low,medium,high',
                'side_effects' => 'nullable|string'
            ]);

            $workaround = $problem->workarounds()->create([
                'title' => $request->title,
                'description' => $request->description,
                'steps' => $request->steps,
                'effectiveness' => $request->effectiveness,
                'side_effects' => $request->side_effects,
                'created_by' => Auth::id(),
                'is_approved' => false
            ]);

            // Log activity
            $this->logActivity($problem, 'workaround_added', 'New workaround added: ' . $workaround->title);

            return response()->json([
                'status' => 'success',
                'message' => 'Workaround added successfully',
                'data' => $workaround
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding workaround: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add workaround'
            ], 500);
        }
    }

    /**
     * Approve workaround.
     */
    public function approveWorkaround(Request $request, Problem $problem, $workaroundId): JsonResponse
    {
        try {
            $workaround = $problem->workarounds()->findOrFail($workaroundId);

            if ($workaround->is_approved) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workaround is already approved'
                ], 400);
            }

            $workaround->update([
                'is_approved' => true,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->notes
            ]);

            // Log activity
            $this->logActivity($problem, 'workaround_approved', 'Workaround approved: ' . $workaround->title);

            // Notify affected users about approved workaround
            // $this->notifyAffectedUsers($problem, $workaround);

            return response()->json([
                'status' => 'success',
                'message' => 'Workaround approved successfully',
                'data' => $workaround
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving workaround: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve workaround'
            ], 500);
        }
    }

    /**
     * Link incidents to problem.
     */
    public function linkIncidents(Request $request, Problem $problem): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'incident_ids' => 'required|array',
                'incident_ids.*' => 'exists:incidents,id'
            ]);

            // Get current incident IDs
            $currentIncidentIds = $problem->incidents()->pluck('id')->toArray();
            
            // Attach new incidents
            $problem->incidents()->syncWithoutDetaching($request->incident_ids);
            
            // Update has_problem flag for newly linked incidents
            Incident::whereIn('id', $request->incident_ids)
                ->update(['has_problem' => true]);

            // Get newly added incidents
            $newIncidentIds = array_diff($request->incident_ids, $currentIncidentIds);
            
            if (count($newIncidentIds) > 0) {
                // Log activity
                $this->logActivity($problem, 'incidents_linked', count($newIncidentIds) . ' new incidents linked to problem');
                
                // Recalculate priority score
                $problem->update([
                    'priority_score' => $this->calculatePriorityScore($problem->toArray())
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Incidents linked successfully',
                'data' => new ProblemResource($problem->fresh(['incidents']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error linking incidents: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to link incidents'
            ], 500);
        }
    }

    /**
     * Unlink incident from problem.
     */
    public function unlinkIncident(Problem $problem, Incident $incident): JsonResponse
    {
        try {
            if (!$problem->incidents->contains($incident->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incident is not linked to this problem'
                ], 400);
            }

            $problem->incidents()->detach($incident->id);
            $incident->update(['has_problem' => false]);

            // Log activity
            $this->logActivity($problem, 'incident_unlinked', "Incident {$incident->incident_number} unlinked from problem");

            return response()->json([
                'status' => 'success',
                'message' => 'Incident unlinked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error unlinking incident: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unlink incident'
            ], 500);
        }
    }

    /**
     * Resolve problem.
     */
    public function resolve(Request $request, Problem $problem): JsonResponse
    {
        DB::beginTransaction();
        try {
            if (!$problem->root_cause) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Root cause must be identified before resolving problem'
                ], 400);
            }

            $request->validate([
                'resolution' => 'required|string',
                'resolution_type' => 'required|in:permanent_fix,workaround,change_implemented,wont_fix',
                'change_id' => 'nullable|exists:changes,id',
                'lessons_learned' => 'nullable|string'
            ]);

            $problem->update([
                'status' => 'resolved',
                'resolution' => $request->resolution,
                'resolution_type' => $request->resolution_type,
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'lessons_learned' => $request->lessons_learned
            ]);

            // Link to change if provided
            if ($request->change_id) {
                $problem->related_changes()->attach($request->change_id);
            }

            // Create final investigation entry
            $this->createInvestigationEntry($problem, 'Problem resolved: ' . $request->resolution);

            // Log activity
            $this->logActivity($problem, 'resolved', 'Problem resolved');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Problem resolved successfully',
                'data' => new ProblemResource($problem)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error resolving problem: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resolve problem'
            ], 500);
        }
    }

    /**
     * Get known errors.
     */
    public function knownErrors(Request $request): JsonResponse
    {
        try {
            $query = DB::table('known_errors')
                ->join('problems', 'known_errors.problem_id', '=', 'problems.id')
                ->where('known_errors.is_active', true);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('known_errors.description', 'like', "%{$search}%")
                      ->orWhere('known_errors.error_code', 'like', "%{$search}%")
                      ->orWhere('problems.title', 'like', "%{$search}%");
                });
            }

            $knownErrors = $query->select([
                'known_errors.*',
                'problems.title as problem_title',
                'problems.problem_number'
            ])->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => $knownErrors
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching known errors: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch known errors'
            ], 500);
        }
    }

    /**
     * Generate unique problem number.
     */
    private function generateProblemNumber(): string
    {
        $prefix = 'PRB';
        $date = now()->format('Ymd');
        $lastProblem = Problem::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastProblem ? intval(substr($lastProblem->problem_number, -4)) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Generate unique error code.
     */
    private function generateErrorCode(): string
    {
        $prefix = 'KE';
        $year = now()->format('Y');
        $lastError = DB::table('known_errors')
            ->where('error_code', 'like', $prefix . $year . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastError ? intval(substr($lastError->error_code, -4)) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, $year, $sequence);
    }

    /**
     * Calculate priority score based on impact and urgency.
     */
    private function calculatePriorityScore(array $data): int
    {
        $impact = $data['impact'] ?? 'medium';
        $urgency = $data['urgency'] ?? 'medium';

        $scores = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4
        ];

        $impactScore = $scores[$impact] ?? 2;
        $urgencyScore = $scores[$urgency] ?? 2;

        return $impactScore * $urgencyScore;
    }

    /**
     * Create investigation entry.
     */
    private function createInvestigationEntry(
        Problem $problem, 
        string $findings, 
        ?string $actionsTaken = null, 
        ?string $nextSteps = null
    ) {
        return $problem->investigations()->create([
            'investigator_id' => Auth::id(),
            'findings' => $findings,
            'actions_taken' => $actionsTaken,
            'next_steps' => $nextSteps,
            'investigated_at' => now()
        ]);
    }

    /**
     * Log activity for problem.
     */
    private function logActivity(Problem $problem, string $action, string $description): void
    {
        $problem->activities()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'performed_at' => now()
        ]);
    }
}