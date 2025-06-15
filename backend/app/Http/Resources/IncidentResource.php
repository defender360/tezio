<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'type' => $this->type,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'reported_at' => $this->reported_at,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'comments' => IncidentCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            
            // Computed attributes
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
            'is_overdue' => $this->when(isset($this->is_overdue), $this->is_overdue),
            'priority_level' => $this->when(isset($this->priority_level), $this->priority_level),
            
            // Conditional attributes based on user permissions
            'internal_notes' => $this->when(
                $request->user()?->can('viewInternalNotes', $this->resource),
                $this->internal_notes
            ),
            'cost_estimate' => $this->when(
                $request->user()?->can('viewFinancialData', $this->resource),
                $this->cost_estimate
            ),
        ];
    }
}