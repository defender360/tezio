<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemResource extends JsonResource
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
            'problem_number' => $this->problem_number,
            'title' => $this->title,
            'description' => $this->description,
            'symptoms' => $this->symptoms,
            'impact' => $this->impact,
            'urgency' => $this->urgency,
            'priority' => $this->priority,
            'status' => $this->status,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'root_cause' => $this->root_cause,
            'workaround' => $this->workaround,
            'permanent_solution' => $this->permanent_solution,
            'assignee' => $this->when($this->assignee_id, [
                'id' => $this->assignee_id,
                'name' => $this->whenLoaded('assignee', fn() => $this->assignee->name),
                'email' => $this->whenLoaded('assignee', fn() => $this->assignee->email),
            ]),
            'assignment_group' => $this->when($this->assignment_group_id, [
                'id' => $this->assignment_group_id,
                'name' => $this->whenLoaded('assignmentGroup', fn() => $this->assignmentGroup->name),
            ]),
            'reporter' => [
                'id' => $this->reporter_id,
                'name' => $this->whenLoaded('reporter', fn() => $this->reporter->name),
                'email' => $this->whenLoaded('reporter', fn() => $this->reporter->email),
            ],
            'investigations' => ProblemInvestigationResource::collection($this->whenLoaded('investigations')),
            'known_error' => new KnownErrorResource($this->whenLoaded('knownError')),
            'related_incidents' => $this->whenLoaded('incidents', fn() => $this->incidents->map(fn($incident) => [
                'id' => $incident->id,
                'incident_number' => $incident->incident_number,
                'title' => $incident->title,
                'status' => $incident->status,
                'created_at' => $incident->created_at->toIso8601String(),
            ])),
            'related_changes' => $this->whenLoaded('changes', fn() => $this->changes->map(fn($change) => [
                'id' => $change->id,
                'change_number' => $change->change_number,
                'title' => $change->title,
                'status' => $change->status,
            ])),
            'affected_services' => $this->whenLoaded('affectedServices', fn() => $this->affectedServices->pluck('name')),
            'affected_cis' => $this->whenLoaded('affectedCIs', fn() => $this->affectedCIs->map(fn($ci) => [
                'id' => $ci->id,
                'name' => $ci->name,
                'type' => $ci->type,
            ])),
            'timeline' => [
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
                'investigation_started_at' => $this->investigation_started_at?->toIso8601String(),
                'root_cause_identified_at' => $this->root_cause_identified_at?->toIso8601String(),
                'resolved_at' => $this->resolved_at?->toIso8601String(),
                'closed_at' => $this->closed_at?->toIso8601String(),
            ],
            'metrics' => [
                'total_incidents' => $this->total_incidents ?? 0,
                'recurring_incidents' => $this->recurring_incidents ?? 0,
                'mttr_hours' => $this->mttr_hours,
                'investigation_hours' => $this->investigation_hours,
                'cost_impact' => $this->cost_impact,
            ],
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'size' => $attachment->size,
                'url' => $attachment->url,
                'type' => $attachment->type,
            ])),
        ];
    }
}