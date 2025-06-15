<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnownErrorResource extends JsonResource
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
            'error_number' => $this->error_number,
            'problem_id' => $this->problem_id,
            'problem' => $this->when($this->relationLoaded('problem'), [
                'problem_number' => $this->problem->problem_number,
                'title' => $this->problem->title,
            ]),
            'title' => $this->title,
            'description' => $this->description,
            'symptoms' => $this->symptoms,
            'root_cause' => $this->root_cause,
            'workaround' => $this->workaround,
            'workaround_steps' => $this->workaround_steps,
            'permanent_fix' => $this->permanent_fix,
            'fix_implementation_steps' => $this->fix_implementation_steps,
            'status' => $this->status,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'affected_services' => $this->whenLoaded('affectedServices', fn() => $this->affectedServices->map(fn($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'criticality' => $service->criticality,
            ])),
            'affected_cis' => $this->whenLoaded('affectedCIs', fn() => $this->affectedCIs->map(fn($ci) => [
                'id' => $ci->id,
                'name' => $ci->name,
                'type' => $ci->type,
            ])),
            'related_incidents_count' => $this->related_incidents_count ?? 0,
            'successful_workaround_applications' => $this->successful_workaround_applications ?? 0,
            'owner' => $this->when($this->owner_id, [
                'id' => $this->owner_id,
                'name' => $this->whenLoaded('owner', fn() => $this->owner->name),
                'email' => $this->whenLoaded('owner', fn() => $this->owner->email),
            ]),
            'created_by' => [
                'id' => $this->created_by_id,
                'name' => $this->whenLoaded('createdBy', fn() => $this->createdBy->name),
            ],
            'verified_by' => $this->when($this->verified_by_id, [
                'id' => $this->verified_by_id,
                'name' => $this->whenLoaded('verifiedBy', fn() => $this->verifiedBy->name),
            ]),
            'knowledge_articles' => $this->whenLoaded('knowledgeArticles', fn() => $this->knowledgeArticles->map(fn($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'url' => route('api.v1.knowledge-articles.show', $article->id),
            ])),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'retired_at' => $this->retired_at?->toIso8601String(),
            'meta' => [
                'is_verified' => $this->is_verified ?? false,
                'is_public' => $this->is_public ?? false,
                'risk_level' => $this->risk_level,
                'workaround_effectiveness' => $this->workaround_effectiveness,
                'fix_eta' => $this->fix_eta?->toIso8601String(),
            ],
        ];
    }
}