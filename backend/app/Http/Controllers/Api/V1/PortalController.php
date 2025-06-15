<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\IncidentResource;
use App\Http\Resources\V1\KnowledgeArticleResource;
use App\Models\Incident;
use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PortalController extends Controller
{
    /**
     * Get portal dashboard statistics for the authenticated user.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = Cache::remember("portal_stats_{$user->id}", 300, function () use ($user) {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            
            return [
                'open_tickets' => Incident::where('reporter_id', $user->id)
                    ->whereIn('status', ['open', 'in-progress'])
                    ->count(),
                    
                'resolved_this_month' => Incident::where('reporter_id', $user->id)
                    ->where('status', 'resolved')
                    ->whereBetween('resolved_at', [$startOfMonth, $now])
                    ->count(),
                    
                'total_tickets' => Incident::where('reporter_id', $user->id)->count(),
                
                'avg_resolution_time' => $this->calculateAverageResolutionTime($user->id),
                
                'resolution_rate' => $this->calculateResolutionRate($user->id),
                
                'knowledge_articles' => KnowledgeArticle::published()->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }

    /**
     * Get user's tickets with filtering and pagination.
     */
    public function tickets(Request $request)
    {
        $query = Incident::where('reporter_id', $request->user()->id)
            ->with(['assignee', 'category']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tickets = $query->paginate($request->input('per_page', 10));

        return IncidentResource::collection($tickets);
    }

    /**
     * Get a specific ticket if the user has access.
     */
    public function showTicket(Request $request, Incident $incident)
    {
        // Check if user has access to this ticket
        if ($incident->reporter_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket');
        }

        return new IncidentResource($incident->load([
            'assignee',
            'category',
            'comments.user',
            'attachments'
        ]));
    }

    /**
     * Create a new ticket from the portal.
     */
    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'category_id' => 'required|exists:categories,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240' // 10MB max
        ]);

        $incident = DB::transaction(function () use ($validated, $request) {
            $incident = Incident::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'category_id' => $validated['category_id'],
                'reporter_id' => $request->user()->id,
                'status' => 'open',
                'source' => 'portal'
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('incident-attachments');
                    $incident->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => $request->user()->id
                    ]);
                }
            }

            // Log activity
            activity()
                ->performedOn($incident)
                ->causedBy($request->user())
                ->log('Ticket created via portal');

            return $incident;
        });

        // Trigger AI analysis for auto-categorization and suggestions
        dispatch(new \App\Jobs\AnalyzeIncident($incident));

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => new IncidentResource($incident)
        ], 201);
    }

    /**
     * Add a comment to a ticket.
     */
    public function addComment(Request $request, Incident $incident): JsonResponse
    {
        // Check access
        if ($incident->reporter_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this ticket');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        $comment = $incident->comments()->create([
            'content' => $validated['content'],
            'user_id' => $request->user()->id,
            'is_public' => true
        ]);

        // Reopen ticket if it was closed
        if ($incident->status === 'closed') {
            $incident->update(['status' => 'open']);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment
        ]);
    }

    /**
     * Search knowledge base articles.
     */
    public function searchKnowledge(Request $request)
    {
        $query = KnowledgeArticle::published();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $articles = $query->orderBy('view_count', 'desc')
            ->orderBy('helpful_count', 'desc')
            ->paginate($request->input('per_page', 10));

        return KnowledgeArticleResource::collection($articles);
    }

    /**
     * Get popular knowledge articles.
     */
    public function popularArticles(Request $request)
    {
        $articles = Cache::remember('popular_kb_articles', 3600, function () {
            return KnowledgeArticle::published()
                ->orderBy('view_count', 'desc')
                ->orderBy('helpful_count', 'desc')
                ->limit(10)
                ->get();
        });

        return KnowledgeArticleResource::collection($articles);
    }

    /**
     * View a knowledge article and increment view count.
     */
    public function viewArticle(KnowledgeArticle $article)
    {
        // Increment view count
        $article->increment('view_count');

        return new KnowledgeArticleResource($article->load('relatedArticles'));
    }

    /**
     * Mark knowledge article as helpful/not helpful.
     */
    public function rateArticle(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'helpful' => 'required|boolean'
        ]);

        $userId = $request->user()->id;
        $ratingKey = "article_rating_{$article->id}_{$userId}";

        // Check if user already rated
        if (Cache::has($ratingKey)) {
            return response()->json([
                'message' => 'You have already rated this article'
            ], 422);
        }

        if ($validated['helpful']) {
            $article->increment('helpful_count');
        } else {
            $article->increment('not_helpful_count');
        }

        // Cache the rating to prevent duplicate ratings
        Cache::put($ratingKey, true, 86400 * 30); // 30 days

        return response()->json([
            'message' => 'Thank you for your feedback'
        ]);
    }

    /**
     * Calculate average resolution time for user's tickets.
     */
    private function calculateAverageResolutionTime($userId): string
    {
        $avgMinutes = Incident::where('reporter_id', $userId)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as avg_minutes')
            ->value('avg_minutes');

        if (!$avgMinutes) {
            return '0h';
        }

        $hours = floor($avgMinutes / 60);
        $minutes = round($avgMinutes % 60);

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    /**
     * Calculate resolution rate percentage.
     */
    private function calculateResolutionRate($userId): float
    {
        $total = Incident::where('reporter_id', $userId)->count();
        if ($total === 0) {
            return 0;
        }

        $resolved = Incident::where('reporter_id', $userId)
            ->whereIn('status', ['resolved', 'closed'])
            ->count();

        return round(($resolved / $total) * 100, 1);
    }
}