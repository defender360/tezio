<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'serial_number' => $this->serial_number,
            'asset_tag' => $this->asset_tag,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'location' => $this->location,
            'description' => $this->description,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_cost' => $this->purchase_cost,
            'warranty_expiry' => $this->warranty_expiry?->format('Y-m-d'),
            'warranty_status' => $this->getWarrantyStatus(),
            'ip_address' => $this->ip_address,
            'mac_address' => $this->mac_address,
            'operating_system' => $this->operating_system,
            'cpu_info' => $this->cpu_info,
            'ram_size' => $this->ram_size,
            'storage_size' => $this->storage_size,
            'attributes' => $this->attributes ?? [],
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                ];
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return new ConfigurationItemResource($this->parent);
            }),
            'children' => ConfigurationItemResource::collection($this->whenLoaded('children')),
            'incidents_count' => $this->whenCounted('incidents'),
            'changes_count' => $this->whenCounted('changes'),
            'related_items' => ConfigurationItemResource::collection($this->whenLoaded('relatedItems')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get warranty status based on expiry date
     */
    private function getWarrantyStatus(): string
    {
        if (!$this->warranty_expiry) {
            return 'no_warranty';
        }

        $now = now();
        if ($this->warranty_expiry->isPast()) {
            return 'expired';
        } elseif ($this->warranty_expiry->diffInDays($now) <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }
}