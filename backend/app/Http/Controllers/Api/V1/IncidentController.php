<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\DTOs\UpdateIncidentData;
use App\Domains\Incident\DTOs\IncidentCommentData;
use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Incident\Actions\UpdateIncidentAction;
use App\Domains\Incident\Actions\AddIncidentCommentAction;
use App\Domains\Incident\Actions\ResolveIncidentAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\IncidentCommentResource;
use App\Http\Requests\CreateIncidentRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\BulkUpdateRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function __construct(
        private CreateIncidentAction $createAction,
        private UpdateIncidentAction $updateAction,
        private AddIncidentCommentAction $addCommentAction,
        private ResolveIncidentAction $resolveAction
    ) {}

    /**
     * Display a listing of incidents with advanced filtering
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $incidents = QueryBuilder::for(Incident::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::scope('created_between'),
                AllowedFilter::scope('overdue'),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('number'),
            ])
            ->allowedSorts([
                'created_at',
                'updated_at',
                'priority',
                'sla_response_target',
                'sla_resolution_target',
                AllowedSort::field('status_order', 'status'),
            ])
            ->allowedIncludes(['assignedUser', 'createdByUser', 'category', 'comments'])
            ->withCount(['comments', 'attachments'])
            ->paginate($request->input('per_page', 20));

        return IncidentResource::collection($incidents);
    }

    /**
     * Store a newly created incident
     */
    public function store(CreateIncidentRequest $request): JsonResponse
    {
        $data = CreateIncidentData::from($request->validated());
        
        $incident = DB::transaction(function () use ($data) {
            return $this->createAction->execute($data);
        });

        return response()->json([
            'message' => 'Incident created successfully',
            'data' => new IncidentResource($incident->load(['assignedUser', 'createdByUser', 'category']))
        ], 201);
    }

    /**
     * Display the specified incident
     */
    public function show(Incident $incident): JsonResponse
    {
        $incident->load([
            'assignedUser',
            'createdByUser', 
            'category',
            'comments.user',
            'attachments',
            'history.user'
        ]);

        return response()->json([
            'data' => new IncidentResource($incident)
        ]);
    }

    /**
     * Update the specified incident
     */
    public function update(UpdateIncidentRequest $request, Incident $incident): JsonResponse
    {
        $data = UpdateIncidentData::from($request->validated());
        
        $incident = DB::transaction(function () use ($data, $incident) {
            return $this->updateAction->execute($incident, $data);
        });

        return response()->json([
            'message' => 'Incident updated successfully',
            'data' => new IncidentResource($incident->fresh(['assignedUser', 'createdByUser', 'category']))
        ]);
    }

    /**
     * Remove the specified incident (soft delete)
     */
    public function destroy(Incident $incident): JsonResponse
    {
        $this->authorize('delete', $incident);

        $incident->delete();

        return response()->json([
            'message' => 'Incident deleted successfully'
        ]);
    }

    /**
     * Assign incident to a user
     */
    public function assign(Request $request, Incident $incident): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $this->authorize('assign', $incident);

        $incident->assignTo($request->user_id);

        return response()->json([
            'message' => 'Incident assigned successfully',
            'data' => new IncidentResource($incident->fresh(['assignedUser']))
        ]);
    }

    /**
     * Add comment to incident
     */
    public function addComment(AddCommentRequest $request, Incident $incident): JsonResponse
    {
        $data = IncidentCommentData::from($request->validated());
        
        $comment = DB::transaction(function () use ($data, $incident) {
            return $this->addCommentAction->execute($incident, $data);
        });

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => new IncidentCommentResource($comment->load('user'))
        ], 201);
    }

    /**
     * Get incident comments
     */
    public function comments(Incident $incident): AnonymousResourceCollection
    {
        $comments = $incident->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return IncidentCommentResource::collection($comments);
    }

    /**
     * Upload attachment to incident
     */
    public function uploadAttachment(Request $request, Incident $incident): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:255'
        ]);

        $this->authorize('update', $incident);

        $path = $request->file('file')->store(
            "incidents/{$incident->id}/attachments",
            'tenant'
        );

        $attachment = $incident->attachments()->create([
            'filename' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'size' => $request->file('file')->getSize(),
            'mime_type' => $request->file('file')->getMimeType(),
            'description' => $request->input('description'),
            'uploaded_by' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'data' => $attachment
        ], 201);
    }

    /**
     * Update incident status
     */
    public function updateStatus(Request $request, Incident $incident): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:new,assigned,in_progress,pending,resolved,closed',
            'resolution_notes' => 'required_if:status,resolved|string'
        ]);

        $this->authorize('updateStatus', $incident);

        if ($request->status === 'resolved') {
            $incident = $this->resolveAction->execute(
                $incident,
                $request->resolution_notes
            );
        } else {
            $incident->updateStatus($request->status);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => new IncidentResource($incident->fresh())
        ]);
    }

    /**
     * Get incident history/timeline
     */
    public function history(Incident $incident): AnonymousResourceCollection
    {
        $history = $incident->history()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $history
        ]);
    }

    /**
     * Bulk update incidents
     */
    public function bulkUpdate(BulkUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $updated = DB::transaction(function () use ($validated) {
            return Incident::whereIn('id', $validated['incident_ids'])
                ->update($validated['updates']);
        });

        return response()->json([
            'message' => "{$updated} incidents updated successfully",
            'updated_count' => $updated
        ]);
    }

    /**
     * Export incidents to CSV/PDF
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,pdf',
            'filters' => 'nullable|array'
        ]);

        // Queue export job
        dispatch(new \App\Jobs\ExportIncidentsJob(
            auth()->user(),
            $request->format,
            $request->filters ?? []
        ));

        return response()->json([
            'message' => 'Export queued. You will receive an email when ready.'
        ]);
    }

    /**
     * Get incident metrics/statistics
     */
    public function metrics(Request $request): JsonResponse
    {
        $metrics = cache()->remember('incident_metrics_' . auth()->user()->tenant_id, 300, function () {
            return [
                'total' => Incident::count(),
                'by_status' => Incident::groupBy('status')
                    ->selectRaw('status, count(*) as count')
                    ->pluck('count', 'status'),
                'by_priority' => Incident::groupBy('priority')
                    ->selectRaw('priority, count(*) as count')
                    ->pluck('count', 'priority'),
                'overdue' => Incident::overdue()->count(),
                'avg_resolution_time' => Incident::whereNotNull('resolved_at')
                    ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/3600) as hours')
                    ->value('hours'),
                'sla_compliance' => [
                    'response' => Incident::slaResponseMet()->count() / max(Incident::count(), 1) * 100,
                    'resolution' => Incident::slaResolutionMet()->count() / max(Incident::count(), 1) * 100,
                ]
            ];
        });

        return response()->json(['data' => $metrics]);
    }
}