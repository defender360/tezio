<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabMeetingResource extends JsonResource
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
            'meeting_number' => $this->meeting_number,
            'title' => $this->title,
            'scheduled_date' => $this->scheduled_date->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'location' => $this->location,
            'meeting_type' => $this->meeting_type,
            'status' => $this->status,
            'chair' => [
                'id' => $this->chair_id,
                'name' => $this->whenLoaded('chair', fn() => $this->chair->name),
                'email' => $this->whenLoaded('chair', fn() => $this->chair->email),
            ],
            'attendees' => $this->whenLoaded('attendees', fn() => $this->attendees->map(fn($attendee) => [
                'id' => $attendee->id,
                'name' => $attendee->name,
                'email' => $attendee->email,
                'role' => $attendee->pivot->role ?? null,
                'attendance_status' => $attendee->pivot->attendance_status ?? null,
            ])),
            'changes' => ChangeResource::collection($this->whenLoaded('changes')),
            'agenda' => $this->agenda,
            'minutes' => $this->minutes,
            'decisions' => $this->decisions,
            'action_items' => $this->action_items,
            'meeting_link' => $this->meeting_link,
            'recording_url' => $this->recording_url,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta' => [
                'total_changes' => $this->whenLoaded('changes', fn() => $this->changes->count()),
                'approved_changes' => $this->whenLoaded('changes', fn() => $this->changes->where('cab_decision', 'approved')->count()),
                'rejected_changes' => $this->whenLoaded('changes', fn() => $this->changes->where('cab_decision', 'rejected')->count()),
            ],
        ];
    }
}