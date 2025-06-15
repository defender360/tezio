<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTaskResource extends JsonResource
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
            'task_number' => $this->task_number,
            'service_request_id' => $this->service_request_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'sequence' => $this->sequence,
            'assignee' => $this->when($this->assignee_id, [
                'id' => $this->assignee_id,
                'name' => $this->whenLoaded('assignee', fn() => $this->assignee->name),
                'email' => $this->whenLoaded('assignee', fn() => $this->assignee->email),
            ]),
            'assignment_group' => $this->when($this->assignment_group_id, [
                'id' => $this->assignment_group_id,
                'name' => $this->whenLoaded('assignmentGroup', fn() => $this->assignmentGroup->name),
            ]),
            'parent_task' => $this->when($this->parent_task_id, [
                'id' => $this->parent_task_id,
                'task_number' => $this->whenLoaded('parentTask', fn() => $this->parentTask->task_number),
                'title' => $this->whenLoaded('parentTask', fn() => $this->parentTask->title),
            ]),
            'subtasks' => ServiceTaskResource::collection($this->whenLoaded('subtasks')),
            'dependencies' => $this->whenLoaded('dependencies', fn() => $this->dependencies->map(fn($dep) => [
                'id' => $dep->id,
                'task_number' => $dep->task_number,
                'title' => $dep->title,
                'status' => $dep->status,
            ])),
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'due_date' => $this->due_date?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completion_notes' => $this->completion_notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta' => [
                'is_milestone' => $this->is_milestone ?? false,
                'is_blocking' => $this->is_blocking ?? false,
                'completion_percentage' => $this->completion_percentage ?? 0,
                'is_overdue' => $this->due_date ? $this->due_date->isPast() && $this->status !== 'completed' : false,
            ],
        ];
    }
}