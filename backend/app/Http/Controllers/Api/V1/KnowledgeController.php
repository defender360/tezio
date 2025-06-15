<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Http\Requests\KnowledgeArticleRequest;
use App\Http\Resources\KnowledgeArticleResource;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class KnowledgeController extends Controller
{
    /**
     * Display a listing of knowledge articles.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = KnowledgeArticle::with(['author', 'category', 'tags']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                // Default to published articles for non-authenticated users
                if (!Auth::check() || !Auth::user()->hasRole(['admin', 'knowledge_manager'])) {
                    $query->where('status', 'published');
                }
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('author_id')) {
                $query->where('author_id', $request->author_id);
            }

            if ($request->has('tag')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('name', $request->tag);
                });
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%")
                      ->orWhereHas('tags', function ($tq) use ($search) {
                          $tq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sort by relevance if searching, otherwise by creation date
            if ($request->has('search')) {
                $query->orderByRaw("
                    CASE 
                        WHEN title LIKE ? THEN 1
                        WHEN summary LIKE ? THEN 2
                        WHEN content LIKE ? THEN 3
                        ELSE 4
                    END", [
                    "%{$request->search}%",
                    "%{$request->search}%",
                    "%{$request->search}%"
                ]);
            }

            $query->orderBy('view_count', 'desc')
                  ->orderBy('created_at', 'desc');

            $articles = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => KnowledgeArticleResource::collection($articles),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching knowledge articles: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch knowledge articles'
            ], 500);
        }
    }

    /**
     * Advanced search with Elasticsearch.
     */
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2',
                'category_id' => 'nullable|exists:knowledge_categories,id',
                'tags' => 'nullable|array',
                'author_id' => 'nullable|exists:users,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'featured' => 'nullable|boolean',
                'sort' => 'nullable|in:relevance,newest,oldest,popular,helpful,rating,title',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            
            $options = [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'sort' => $request->get('sort', 'relevance'),
                'category_id' => $request->get('category_id'),
                'tags' => $request->get('tags'),
                'author_id' => $request->get('author_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'featured' => $request->get('featured'),
                'tenant_id' => Auth::user()?->tenant_id,
            ];

            $results = $searchService->advancedSearch($request->get('query'), $options);
            
            // Log search for analytics
            $this->logSearch($request->get('query'), $results['total']);

            // Get article models for resource transformation
            $articleIds = collect($results['articles'])->pluck('id')->toArray();
            $articles = KnowledgeArticle::with(['author', 'category'])
                ->whereIn('id', $articleIds)
                ->get()
                ->keyBy('id');

            // Sort articles by search result order
            $sortedArticles = collect($articleIds)->map(function ($id) use ($articles) {
                return $articles->get($id);
            })->filter();

            // Add highlights to articles
            $articlesWithHighlights = $sortedArticles->map(function ($article, $index) use ($results) {
                if ($article && isset($results['articles'][$index]['highlights'])) {
                    $article->highlights = $results['articles'][$index]['highlights'];
                }
                return $article;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'articles' => KnowledgeArticleResource::collection($articlesWithHighlights),
                    'facets' => $results['facets'],
                    'suggestions' => $results['suggestions'],
                    'total' => $results['total'],
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($results['total'] / $perPage),
                    'took' => $results['took'],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching knowledge articles: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search knowledge articles'
            ], 500);
        }
    }

    /**
     * Autocomplete search endpoint.
     */
    public function autocomplete(Request $request, SearchService $searchService): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2',
                'limit' => 'nullable|integer|min:1|max:10'
            ]);

            $suggestions = $searchService->autocomplete(
                $request->get('query'),
                $request->get('limit', 5)
            );

            return response()->json([
                'status' => 'success',
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            Log::error('Error in autocomplete: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get autocomplete suggestions'
            ], 500);
        }
    }

    /**
     * Get similar articles.
     */
    public function similar(KnowledgeArticle $knowledgeArticle, SearchService $searchService): JsonResponse
    {
        try {
            $similarArticles = $searchService->findSimilar($knowledgeArticle, 5);

            return response()->json([
                'status' => 'success',
                'data' => KnowledgeArticleResource::collection($similarArticles)
            ]);
        } catch (\Exception $e) {
            Log::error('Error finding similar articles: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to find similar articles'
            ], 500);
        }
    }

    /**
     * Get trending searches.
     */
    public function trending(Request $request, SearchService $searchService): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:30',
                'limit' => 'nullable|integer|min:1|max:20'
            ]);

            $trending = $searchService->getTrendingSearches(
                $request->get('days', 7),
                $request->get('limit', 10)
            );

            return response()->json([
                'status' => 'success',
                'data' => $trending
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting trending searches: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get trending searches'
            ], 500);
        }
    }

    /**
     * Reindex all articles.
     */
    public function reindex(SearchService $searchService): JsonResponse
    {
        try {
            // Check if user has permission to reindex
            if (!Auth::user()->hasRole(['admin', 'knowledge_manager'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to perform reindex'
                ], 403);
            }

            $result = $searchService->reindexAll();

            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => [
                    'indexed' => $result['indexed'] ?? 0
                ]
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Error reindexing articles: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reindex articles'
            ], 500);
        }
    }

    /**
     * Get search health status.
     */
    public function searchHealth(SearchService $searchService): JsonResponse
    {
        try {
            $health = $searchService->healthCheck();

            return response()->json([
                'status' => 'success',
                'data' => $health
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking search health: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check search health'
            ], 500);
        }
    }

    /**
     * Store a newly created knowledge article.
     */
    public function store(KnowledgeArticleRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['author_id'] = Auth::id();
            $data['slug'] = Str::slug($data['title']);
            $data['status'] = $request->publish ? 'published' : 'draft';
            
            if ($data['status'] === 'published') {
                $data['published_at'] = now();
            }

            // Generate AI summary if not provided
            if (!isset($data['summary']) && isset($data['content'])) {
                $data['summary'] = $this->generateSummary($data['content']);
            }

            $article = KnowledgeArticle::create($data);

            // Handle tags
            if ($request->has('tags')) {
                $this->syncTags($article, $request->tags);
            }

            // Handle attachments
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($article, $request->file('attachments'));
            }

            // Create initial version
            $this->createVersion($article, 'Initial version');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge article created successfully',
                'data' => new KnowledgeArticleResource($article->load(['author', 'category', 'tags']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating knowledge article: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create knowledge article'
            ], 500);
        }
    }

    /**
     * Display the specified knowledge article.
     */
    public function show(KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        try {
            // Check if user can view draft articles
            if ($knowledgeArticle->status === 'draft' && 
                (!Auth::check() || 
                 (Auth::id() !== $knowledgeArticle->author_id && 
                  !Auth::user()->hasRole(['admin', 'knowledge_manager'])))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Article not found'
                ], 404);
            }

            $knowledgeArticle->load([
                'author', 
                'category', 
                'tags', 
                'attachments',
                'ratings' => function ($query) {
                    $query->where('user_id', Auth::id());
                }
            ]);

            // Increment view count
            $knowledgeArticle->increment('view_count');

            // Get related articles
            $relatedArticles = $this->getRelatedArticles($knowledgeArticle);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'article' => new KnowledgeArticleResource($knowledgeArticle),
                    'related_articles' => KnowledgeArticleResource::collection($relatedArticles),
                    'user_rating' => $knowledgeArticle->ratings->first()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching knowledge article: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch knowledge article'
            ], 500);
        }
    }

    /**
     * Update the specified knowledge article.
     */
    public function update(KnowledgeArticleRequest $request, KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if user can update
            if (!$this->canUpdateArticle($knowledgeArticle)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this article'
                ], 403);
            }

            // Create version before updating
            $this->createVersion($knowledgeArticle, $request->version_notes ?? 'Updated article');

            $data = $request->validated();
            
            // Handle publishing
            if ($request->publish && $knowledgeArticle->status === 'draft') {
                $data['status'] = 'published';
                $data['published_at'] = now();
            }

            // Update slug if title changed
            if (isset($data['title']) && $data['title'] !== $knowledgeArticle->title) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Regenerate summary if content changed significantly
            if (isset($data['content']) && !isset($data['summary'])) {
                $data['summary'] = $this->generateSummary($data['content']);
            }

            $knowledgeArticle->update($data);

            // Update tags
            if ($request->has('tags')) {
                $this->syncTags($knowledgeArticle, $request->tags);
            }

            // Handle new attachments
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($knowledgeArticle, $request->file('attachments'));
            }

            DB::commit();

            // Clear cache
            Cache::forget("article_{$knowledgeArticle->slug}");

            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge article updated successfully',
                'data' => new KnowledgeArticleResource($knowledgeArticle->fresh(['author', 'category', 'tags']))
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating knowledge article: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update knowledge article'
            ], 500);
        }
    }

    /**
     * Remove the specified knowledge article.
     */
    public function destroy(KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        try {
            // Check if user can delete
            if (!$this->canDeleteArticle($knowledgeArticle)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this article'
                ], 403);
            }

            // Soft delete to preserve history
            $knowledgeArticle->delete();

            // Clear cache
            Cache::forget("article_{$knowledgeArticle->slug}");

            return response()->json([
                'status' => 'success',
                'message' => 'Knowledge article deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting knowledge article: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete knowledge article'
            ], 500);
        }
    }

    /**
     * Rate knowledge article.
     */
    public function rate(Request $request, KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        try {
            $request->validate([
                'rating' => 'required|integer|between:1,5',
                'feedback' => 'nullable|string|max:500'
            ]);

            // Check if user already rated
            $existingRating = $knowledgeArticle->ratings()
                ->where('user_id', Auth::id())
                ->first();

            if ($existingRating) {
                $existingRating->update([
                    'rating' => $request->rating,
                    'feedback' => $request->feedback
                ]);
            } else {
                $knowledgeArticle->ratings()->create([
                    'user_id' => Auth::id(),
                    'rating' => $request->rating,
                    'feedback' => $request->feedback
                ]);
            }

            // Update article average rating
            $avgRating = $knowledgeArticle->ratings()->avg('rating');
            $knowledgeArticle->update(['average_rating' => round($avgRating, 2)]);

            return response()->json([
                'status' => 'success',
                'message' => 'Article rated successfully',
                'data' => [
                    'average_rating' => $knowledgeArticle->average_rating,
                    'total_ratings' => $knowledgeArticle->ratings()->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error rating article: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to rate article'
            ], 500);
        }
    }

    /**
     * Mark article as helpful/not helpful.
     */
    public function markHelpful(Request $request, KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        try {
            $request->validate([
                'helpful' => 'required|boolean'
            ]);

            $vote = $knowledgeArticle->helpfulness_votes()
                ->where('user_id', Auth::id())
                ->first();

            if ($vote) {
                $vote->update(['is_helpful' => $request->helpful]);
            } else {
                $knowledgeArticle->helpfulness_votes()->create([
                    'user_id' => Auth::id(),
                    'is_helpful' => $request->helpful
                ]);
            }

            // Update counts
            $helpfulCount = $knowledgeArticle->helpfulness_votes()->where('is_helpful', true)->count();
            $notHelpfulCount = $knowledgeArticle->helpfulness_votes()->where('is_helpful', false)->count();
            
            $knowledgeArticle->update([
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => $notHelpfulCount
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback recorded successfully',
                'data' => [
                    'helpful_count' => $helpfulCount,
                    'not_helpful_count' => $notHelpfulCount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking article helpfulness: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record feedback'
            ], 500);
        }
    }

    /**
     * Get article versions.
     */
    public function versions(KnowledgeArticle $knowledgeArticle): JsonResponse
    {
        try {
            // Check if user can view versions
            if (!$this->canViewVersions($knowledgeArticle)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to view article versions'
                ], 403);
            }

            $versions = $knowledgeArticle->versions()
                ->with('editor')
                ->orderBy('version_number', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data' => $versions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching article versions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch article versions'
            ], 500);
        }
    }

    /**
     * Restore specific version.
     */
    public function restoreVersion(KnowledgeArticle $knowledgeArticle, $versionId): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if user can restore
            if (!$this->canUpdateArticle($knowledgeArticle)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to restore article versions'
                ], 403);
            }

            $version = $knowledgeArticle->versions()->findOrFail($versionId);

            // Create new version from current state
            $this->createVersion($knowledgeArticle, 'Before restoring to version ' . $version->version_number);

            // Restore content from version
            $knowledgeArticle->update([
                'title' => $version->title,
                'content' => $version->content,
                'summary' => $version->summary
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Article version restored successfully',
                'data' => new KnowledgeArticleResource($knowledgeArticle)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error restoring article version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore article version'
            ], 500);
        }
    }

    /**
     * Get categories.
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $query = KnowledgeCategory::withCount('articles');

            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } else if ($request->has('root_only')) {
                $query->whereNull('parent_id');
            }

            $categories = $query->orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }

    /**
     * Create category.
     */
    public function createCategory(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:knowledge_categories',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:knowledge_categories,id',
                'icon' => 'nullable|string|max:50'
            ]);

            $category = KnowledgeCategory::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'icon' => $request->icon
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create category'
            ], 500);
        }
    }

    /**
     * Get AI-powered article suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $suggestions = [];

            // Get suggestions based on user's recent activity
            if (Auth::check()) {
                $recentViews = DB::table('article_views')
                    ->where('user_id', Auth::id())
                    ->orderBy('viewed_at', 'desc')
                    ->limit(5)
                    ->pluck('article_id');

                if ($recentViews->isNotEmpty()) {
                    $suggestions['based_on_history'] = $this->getSuggestionsBasedOnArticles($recentViews);
                }
            }

            // Get trending articles
            $suggestions['trending'] = KnowledgeArticle::where('status', 'published')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('view_count', 'desc')
                ->limit(5)
                ->get();

            // Get highly rated articles
            $suggestions['top_rated'] = KnowledgeArticle::where('status', 'published')
                ->where('average_rating', '>=', 4)
                ->orderBy('average_rating', 'desc')
                ->limit(5)
                ->get();

            // Get recently updated
            $suggestions['recently_updated'] = KnowledgeArticle::where('status', 'published')
                ->where('updated_at', '>=', now()->subDays(7))
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting suggestions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get suggestions'
            ], 500);
        }
    }

    /**
     * Generate summary using AI.
     */
    private function generateSummary(string $content): string
    {
        // This is a placeholder for AI integration
        // In production, this would call an AI service
        $sentences = preg_split('/(?<=[.!?])\s+/', strip_tags($content));
        $summary = implode(' ', array_slice($sentences, 0, 3));
        
        return Str::limit($summary, 200);
    }

    /**
     * Get related articles.
     */
    private function getRelatedArticles(KnowledgeArticle $article): \Illuminate\Support\Collection
    {
        // Get articles from same category
        $relatedByCategory = KnowledgeArticle::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->limit(3)
            ->get();

        // Get articles with similar tags
        $tagIds = $article->tags->pluck('id');
        $relatedByTags = KnowledgeArticle::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('id', $tagIds);
            })
            ->limit(3)
            ->get();

        return $relatedByCategory->merge($relatedByTags)->unique('id')->take(5);
    }

    /**
     * Get related searches.
     */
    private function getRelatedSearches(string $query): array
    {
        // This is a simplified version
        // In production, this would use search history and AI
        return Cache::remember("related_searches_{$query}", 3600, function () use ($query) {
            return [
                $query . ' troubleshooting',
                $query . ' best practices',
                $query . ' guide',
                'how to ' . $query,
                $query . ' tips'
            ];
        });
    }

    /**
     * Get AI suggestions for search.
     */
    private function getAiSuggestions(string $query, $articles): array
    {
        // Placeholder for AI integration
        return [
            'did_you_mean' => null,
            'related_topics' => [],
            'suggested_actions' => []
        ];
    }

    /**
     * Get suggestions based on articles.
     */
    private function getSuggestionsBasedOnArticles($articleIds): \Illuminate\Support\Collection
    {
        $categories = KnowledgeArticle::whereIn('id', $articleIds)
            ->pluck('category_id')
            ->unique();

        return KnowledgeArticle::where('status', 'published')
            ->whereNotIn('id', $articleIds)
            ->whereIn('category_id', $categories)
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Sync tags for article.
     */
    private function syncTags(KnowledgeArticle $article, array $tags): void
    {
        $tagIds = [];
        
        foreach ($tags as $tagName) {
            $tag = DB::table('tags')->where('name', $tagName)->first();
            
            if (!$tag) {
                $tagId = DB::table('tags')->insertGetId([
                    'name' => $tagName,
                    'slug' => Str::slug($tagName),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $tagIds[] = $tagId;
            } else {
                $tagIds[] = $tag->id;
            }
        }

        $article->tags()->sync($tagIds);
    }

    /**
     * Handle article attachments.
     */
    private function handleAttachments(KnowledgeArticle $article, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('knowledge/' . $article->id, 'public');
            
            $article->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => Auth::id()
            ]);
        }
    }

    /**
     * Create article version.
     */
    private function createVersion(KnowledgeArticle $article, string $notes): void
    {
        $lastVersion = $article->versions()->orderBy('version_number', 'desc')->first();
        $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

        $article->versions()->create([
            'version_number' => $versionNumber,
            'title' => $article->title,
            'content' => $article->content,
            'summary' => $article->summary,
            'editor_id' => Auth::id(),
            'notes' => $notes
        ]);
    }

    /**
     * Log search query.
     */
    private function logSearch(string $query, int $resultCount): void
    {
        DB::table('search_logs')->insert([
            'query' => $query,
            'user_id' => Auth::id(),
            'result_count' => $resultCount,
            'created_at' => now()
        ]);
    }

    /**
     * Check if user can update article.
     */
    private function canUpdateArticle(KnowledgeArticle $article): bool
    {
        $user = Auth::user();
        
        if ($user->id === $article->author_id) {
            return true;
        }

        if ($user->hasRole(['admin', 'knowledge_manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete article.
     */
    private function canDeleteArticle(KnowledgeArticle $article): bool
    {
        $user = Auth::user();
        
        if ($user->hasRole(['admin', 'knowledge_manager'])) {
            return true;
        }

        if ($user->id === $article->author_id && $article->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view versions.
     */
    private function canViewVersions(KnowledgeArticle $article): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        if ($user->id === $article->author_id) {
            return true;
        }

        if ($user->hasRole(['admin', 'knowledge_manager'])) {
            return true;
        }

        return false;
    }
}