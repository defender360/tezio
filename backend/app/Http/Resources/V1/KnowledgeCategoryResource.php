<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeCategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'parent_category' => $this->when($this->parent_id, [
                'id' => $this->parent_id,
                'name' => $this->whenLoaded('parent', fn() => $this->parent->name),
                'slug' => $this->whenLoaded('parent', fn() => $this->parent->slug),
            ]),
            'subcategories' => KnowledgeCategoryResource::collection($this->whenLoaded('children')),
            'breadcrumb' => $this->whenLoaded('ancestors', fn() => $this->ancestors->map(fn($ancestor) => [
                'id' => $ancestor->id,
                'name' => $ancestor->name,
                'slug' => $ancestor->slug,
            ])),
            'articles_count' => $this->whenCounted('articles'),
            'is_active' => $this->is_active ?? true,
            'display_order' => $this->display_order ?? 0,
            'access_level' => $this->access_level ?? 'public',
            'allowed_roles' => $this->allowed_roles,
            'metadata' => [
                'seo_title' => $this->seo_title,
                'seo_description' => $this->seo_description,
                'seo_keywords' => $this->seo_keywords,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}