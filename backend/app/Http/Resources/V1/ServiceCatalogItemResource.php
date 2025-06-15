<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCatalogItemResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'category' => $this->category,
            'subcategory' => $this->subcategory,
            'type' => $this->type,
            'status' => $this->status,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'cost' => $this->cost,
            'currency' => $this->currency,
            'unit' => $this->unit,
            'requires_approval' => $this->requires_approval ?? false,
            'approval_levels' => $this->approval_levels,
            'sla' => [
                'response_time' => $this->sla_response_time,
                'resolution_time' => $this->sla_resolution_time,
                'unit' => $this->sla_unit ?? 'hours',
            ],
            'form_fields' => $this->form_fields,
            'fulfillment_method' => $this->fulfillment_method,
            'assignment_group' => $this->when($this->assignment_group_id, [
                'id' => $this->assignment_group_id,
                'name' => $this->whenLoaded('assignmentGroup', fn() => $this->assignmentGroup->name),
            ]),
            'prerequisites' => $this->prerequisites,
            'delivery_time' => $this->delivery_time,
            'keywords' => $this->keywords,
            'order_guide' => $this->order_guide,
            'is_bundle' => $this->is_bundle ?? false,
            'bundle_items' => $this->whenLoaded('bundleItems', fn() => $this->bundleItems->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity ?? 1,
            ])),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'meta' => [
                'is_featured' => $this->is_featured ?? false,
                'popularity_score' => $this->popularity_score ?? 0,
                'average_rating' => $this->average_rating,
                'total_requests' => $this->total_requests ?? 0,
                'is_available' => $this->is_available ?? true,
            ],
        ];
    }
}