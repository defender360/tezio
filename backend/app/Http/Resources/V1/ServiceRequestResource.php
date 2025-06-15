<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
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
            'request_number' => $this->request_number,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'priority' => $this->priority,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'catalog_item' => new ServiceCatalogItemResource($this->whenLoaded('catalogItem')),
            'requester' => [
                'id' => $this->requester_id,
                'name' => $this->whenLoaded('requester', fn() => $this->requester->name),
                'email' => $this->whenLoaded('requester', fn() => $this->requester->email),
                'department' => $this->whenLoaded('requester', fn() => $this->requester->department),
            ],
            'assignee' => $this->when($this->assignee_id, [
                'id' => $this->assignee_id,
                'name' => $this->whenLoaded('assignee', fn() => $this->assignee->name),
                'email' => $this->whenLoaded('assignee', fn() => $this->assignee->email),
            ]),
            'assignment_group' => $this->when($this->assignment_group_id, [
                'id' => $this->assignment_group_id,
                'name' => $this->whenLoaded('assignmentGroup', fn() => $this->assignmentGroup->name),
            ]),
            'tasks' => ServiceTaskResource::collection($this->whenLoaded('tasks')),
            'approvals' => $this->whenLoaded('approvals', fn() => $this->approvals->map(fn($approval) => [
                'id' => $approval->id,
                'approver_name' => $approval->approver->name ?? null,
                'status' => $approval->status,
                'approved_at' => $approval->approved_at?->toIso8601String(),
            ])),
            'additional_info' => $this->additional_info,
            'fulfillment_notes' => $this->fulfillment_notes,
            'closure_notes' => $this->closure_notes,
            'requested_due_date' => $this->requested_due_date?->toIso8601String(),
            'actual_completion_date' => $this->actual_completion_date?->toIso8601String(),
            'sla_due_date' => $this->sla_due_date?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'meta' => [
                'requires_approval' => $this->requires_approval ?? false,
                'is_escalated' => $this->is_escalated ?? false,
                'completion_percentage' => $this->completion_percentage ?? 0,
                'sla_status' => $this->sla_status,
                'satisfaction_rating' => $this->satisfaction_rating,
                'total_cost' => $this->total_cost,
            ],
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'size' => $attachment->size,
                'url' => $attachment->url,
            ])),
        ];
    }
}