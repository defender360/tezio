<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProblemCollection extends ResourceCollection
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
                'total_open' => $this->collection->whereIn('status', ['new', 'investigating', 'identified'])->count(),
                'total_resolved' => $this->collection->where('status', 'resolved')->count(),
                'total_known_errors' => $this->collection->whereNotNull('known_error_id')->count(),
                'high_priority' => $this->collection->where('priority', 'high')->count(),
                'critical_priority' => $this->collection->where('priority', 'critical')->count(),
                'avg_resolution_days' => $this->calculateAverageResolutionDays(),
            ],
        ];
    }

    /**
     * Calculate average resolution days for resolved problems.
     */
    private function calculateAverageResolutionDays(): ?float
    {
        $resolved = $this->collection->filter(fn($problem) => 
            $problem->status === 'resolved' && 
            $problem->resolved_at && 
            $problem->created_at
        );

        if ($resolved->isEmpty()) {
            return null;
        }

        $totalDays = $resolved->sum(fn($problem) => 
            $problem->resolved_at->diffInDays($problem->created_at)
        );

        return round($totalDays / $resolved->count(), 1);
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
                'self' => route('api.v1.problems.index'),
            ],
        ];
    }
}