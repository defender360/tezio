<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleVersionResource extends JsonResource
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
            'article_id' => $this->article_id,
            'version_number' => $this->version_number,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'change_summary' => $this->change_summary,
            'change_type' => $this->change_type,
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
            'status' => $this->status,
            'is_major_version' => $this->is_major_version ?? false,
            'is_current' => $this->is_current ?? false,
            'created_at' => $this->created_at->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'differences' => $this->when($request->has('show_diff'), fn() => $this->differences),
            'metadata' => [
                'word_count' => $this->word_count,
                'reading_time' => $this->reading_time,
                'has_breaking_changes' => $this->has_breaking_changes ?? false,
            ],
            'compare_url' => $this->when($this->previous_version_id, fn() => 
                route('api.v1.article-versions.compare', [
                    'article' => $this->article_id,
                    'from' => $this->previous_version_id,
                    'to' => $this->id
                ])
            ),
        ];
    }
}