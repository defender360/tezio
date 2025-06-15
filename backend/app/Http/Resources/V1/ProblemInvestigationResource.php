<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemInvestigationResource extends JsonResource
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
            'problem_id' => $this->problem_id,
            'investigation_type' => $this->investigation_type,
            'title' => $this->title,
            'hypothesis' => $this->hypothesis,
            'methodology' => $this->methodology,
            'findings' => $this->findings,
            'evidence' => $this->evidence,
            'conclusion' => $this->conclusion,
            'status' => $this->status,
            'investigator' => [
                'id' => $this->investigator_id,
                'name' => $this->whenLoaded('investigator', fn() => $this->investigator->name),
                'email' => $this->whenLoaded('investigator', fn() => $this->investigator->email),
            ],
            'participants' => $this->whenLoaded('participants', fn() => $this->participants->map(fn($participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'role' => $participant->pivot->role ?? null,
            ])),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'effort_hours' => $this->effort_hours,
            'tools_used' => $this->tools_used,
            'test_results' => $this->test_results,
            'recommendations' => $this->recommendations,
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'type' => $attachment->type,
                'url' => $attachment->url,
            ])),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta' => [
                'is_root_cause_found' => $this->is_root_cause_found ?? false,
                'confidence_level' => $this->confidence_level,
                'requires_followup' => $this->requires_followup ?? false,
            ],
        ];
    }
}