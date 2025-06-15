<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article_number' => $this->article_number,
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => $this->when(!$request->routeIs('*.index'), $this->content),
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'category' => new KnowledgeCategoryResource($this->whenLoaded('category')),
            'subcategories' => $this->whenLoaded('subcategories', fn() => $this->subcategories->pluck('name')),
            'tags' => $this->whenLoaded('tags', fn() => $this->tags->pluck('name')),
            'author' => [
                'id' => $this->author_id,
                'name' => $this->whenLoaded('author', fn() => $this->author->name),
                'email' => $this->whenLoaded('author', fn() => $this->author->email),
            ],
            'reviewer' => $this->when($this->reviewer_id, [
                'id' => $this->reviewer_id,
                'name' => $this->whenLoaded('reviewer', fn() => $this->reviewer->name),
                'email' => $this->whenLoaded('reviewer', fn() => $this->reviewer->email),
            ]),
            'versions' => ArticleVersionResource::collection($this->whenLoaded('versions')),
            'current_version' => $this->current_version,
            'related_articles' => $this->whenLoaded('relatedArticles', fn() => $this->relatedArticles->map(fn($article) => [
                'id' => $article->id,
                'article_number' => $article->article_number,
                'title' => $article->title,
                'type' => $article->type,
            ])),
            'related_problems' => $this->whenLoaded('problems', fn() => $this->problems->map(fn($problem) => [
                'id' => $problem->id,
                'problem_number' => $problem->problem_number,
                'title' => $problem->title,
            ])),
            'related_known_errors' => $this->whenLoaded('knownErrors', fn() => $this->knownErrors->map(fn($error) => [
                'id' => $error->id,
                'error_number' => $error->error_number,
                'title' => $error->title,
            ])),
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'size' => $attachment->size,
                'type' => $attachment->type,
                'url' => $attachment->url,
            ])),
            'metadata' => [
                'keywords' => $this->keywords,
                'target_audience' => $this->target_audience,
                'difficulty_level' => $this->difficulty_level,
                'estimated_reading_time' => $this->estimated_reading_time,
                'prerequisites' => $this->prerequisites,
            ],
            'lifecycle' => [
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
                'published_at' => $this->published_at?->toIso8601String(),
                'reviewed_at' => $this->reviewed_at?->toIso8601String(),
                'expires_at' => $this->expires_at?->toIso8601String(),
                'retired_at' => $this->retired_at?->toIso8601String(),
                'next_review_date' => $this->next_review_date?->toIso8601String(),
            ],
            'statistics' => [
                'views_count' => $this->views_count ?? 0,
                'helpful_count' => $this->helpful_count ?? 0,
                'not_helpful_count' => $this->not_helpful_count ?? 0,
                'average_rating' => $this->average_rating,
                'comments_count' => $this->whenLoaded('comments', fn() => $this->comments->count()) ?? $this->comments_count ?? 0,
                'bookmarks_count' => $this->bookmarks_count ?? 0,
            ],
            'permissions' => [
                'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
                'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'can_publish' => $request->user()?->can('publish', $this->resource) ?? false,
            ],
        ];
    }
}