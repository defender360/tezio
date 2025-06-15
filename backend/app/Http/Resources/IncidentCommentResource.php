<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentCommentResource extends JsonResource
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
            'incident_id' => $this->incident_id,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'incident' => new IncidentResource($this->whenLoaded('incident')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'mentions' => UserResource::collection($this->whenLoaded('mentions')),
            
            // Parent/child relationship for threaded comments
            'parent_id' => $this->parent_id,
            'parent' => new IncidentCommentResource($this->whenLoaded('parent')),
            'replies' => IncidentCommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenCounted('replies'),
            
            // Additional metadata
            'is_edited' => $this->created_at->ne($this->updated_at),
            'can_edit' => $request->user()?->can('update', $this->resource),
            'can_delete' => $request->user()?->can('delete', $this->resource),
            
            // Activity tracking
            'read_by' => UserResource::collection($this->whenLoaded('readBy')),
            'is_read' => $this->when(
                $request->user() && $this->relationLoaded('readBy'),
                fn() => $this->readBy->contains('id', $request->user()->id)
            ),
        ];
    }
}