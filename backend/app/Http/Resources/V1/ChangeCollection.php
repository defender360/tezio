<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ChangeCollection extends ResourceCollection
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
                'total_emergency' => $this->collection->where('is_emergency', true)->count(),
                'pending_approval' => $this->collection->where('approval_status', 'pending')->count(),
                'in_progress' => $this->collection->where('status', 'in_progress')->count(),
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
                'self' => route('api.v1.changes.index'),
            ],
        ];
    }
}