<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeResource extends JsonResource
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
            'change_number' => $this->change_number,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'risk_level' => $this->risk_level,
            'impact_level' => $this->impact_level,
            'implementation_plan' => $this->implementation_plan,
            'rollback_plan' => $this->rollback_plan,
            'test_plan' => $this->test_plan,
            'scheduled_start_date' => $this->scheduled_start_date?->toIso8601String(),
            'scheduled_end_date' => $this->scheduled_end_date?->toIso8601String(),
            'actual_start_date' => $this->actual_start_date?->toIso8601String(),
            'actual_end_date' => $this->actual_end_date?->toIso8601String(),
            'requester' => [
                'id' => $this->requester_id,
                'name' => $this->whenLoaded('requester', fn() => $this->requester->name),
                'email' => $this->whenLoaded('requester', fn() => $this->requester->email),
            ],
            'assignee' => $this->when($this->assignee_id, [
                'id' => $this->assignee_id,
                'name' => $this->whenLoaded('assignee', fn() => $this->assignee->name),
                'email' => $this->whenLoaded('assignee', fn() => $this->assignee->email),
            ]),
            'approvals' => ChangeApprovalResource::collection($this->whenLoaded('approvals')),
            'cab_meeting' => new CabMeetingResource($this->whenLoaded('cabMeeting')),
            'affected_services' => $this->whenLoaded('affectedServices', fn() => $this->affectedServices->pluck('name')),
            'related_incidents' => $this->whenLoaded('relatedIncidents', fn() => $this->relatedIncidents->pluck('incident_number')),
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'size' => $attachment->size,
                'url' => $attachment->url,
            ])),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta' => [
                'is_emergency' => $this->is_emergency ?? false,
                'requires_cab_approval' => $this->requires_cab_approval ?? true,
                'approval_status' => $this->approval_status,
                'completion_percentage' => $this->completion_percentage ?? 0,
            ],
        ];
    }
}