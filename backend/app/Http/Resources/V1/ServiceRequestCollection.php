<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ServiceRequestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_pending' => $this->collection->where('status', 'pending')->count(),
                'total_in_progress' => $this->collection->where('status', 'in_progress')->count(),
                'total_completed' => $this->collection->where('status', 'completed')->count(),
                'requires_approval' => $this->collection->where('requires_approval', true)->count(),
                'overdue' => $this->collection->where('sla_status', 'breached')->count(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.v1.service-requests.index'),
            ],
        ];
    }
}