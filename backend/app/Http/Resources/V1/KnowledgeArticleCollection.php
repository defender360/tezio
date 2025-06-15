<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class KnowledgeArticleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_published' => $this->collection->where('status', 'published')->count(),
                'total_draft' => $this->collection->where('status', 'draft')->count(),
                'total_under_review' => $this->collection->where('status', 'under_review')->count(),
                'total_expired' => $this->collection->filter(fn($article) => 
                    $article->expires_at && $article->expires_at->isPast()
                )->count(),
                'needs_review' => $this->collection->filter(fn($article) => 
                    $article->next_review_date && $article->next_review_date->isPast()
                )->count(),
                'categories' => $this->getCategorySummary(),
            ],
        ];
    }

    /**
     * Get category summary for the collection.
     */
    private function getCategorySummary(): array
    {
        return $this->collection
            ->groupBy('category.name')
            ->map(fn($articles, $category) => [
                'name' => $category,
                'count' => $articles->count(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.v1.knowledge-articles.index'),
            ],
        ];
    }
}