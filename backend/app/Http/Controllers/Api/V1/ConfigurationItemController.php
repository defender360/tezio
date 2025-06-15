<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ConfigurationItem\CreateConfigurationItemRequest;
use App\Http\Requests\V1\ConfigurationItem\UpdateConfigurationItemRequest;
use App\Http\Resources\V1\ConfigurationItemResource;
use App\Http\Resources\V1\ConfigurationItemCollection;
use App\Models\ConfigurationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ConfigurationItemController extends Controller
{
    /**
     * Display a listing of configuration items.
     */
    public function index(Request $request): ConfigurationItemCollection
    {
        $query = ConfigurationItem::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', $request->input('location'));
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // Filter by owner
        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->input('owner_id'));
        }

        // Date range filters
        if ($request->has('purchased_from')) {
            $query->whereDate('purchase_date', '>=', $request->input('purchased_from'));
        }
        if ($request->has('purchased_to')) {
            $query->whereDate('purchase_date', '<=', $request->input('purchased_to'));
        }

        // Warranty expiry filter
        if ($request->has('warranty_expiring_days')) {
            $days = $request->input('warranty_expiring_days');
            $query->whereDate('warranty_expiry', '<=', Carbon::now()->addDays($days))
                  ->whereDate('warranty_expiry', '>=', Carbon::now());
        }

        // Include relationships
        $query->with(['owner', 'department', 'parent', 'incidents', 'changes']);

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        return new ConfigurationItemCollection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Store a newly created configuration item.
     */
    public function store(CreateConfigurationItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $configItem = DB::transaction(function () use ($validated) {
            $configItem = ConfigurationItem::create($validated);
            
            // If relationships are provided, sync them
            if (isset($validated['related_items'])) {
                $configItem->relatedItems()->sync($validated['related_items']);
            }
            
            // Log the creation
            activity()
                ->performedOn($configItem)
                ->causedBy(auth()->user())
                ->log('Configuration item created');
            
            return $configItem;
        });

        return response()->json([
            'message' => 'Configuration item created successfully',
            'data' => new ConfigurationItemResource($configItem->load(['owner', 'department', 'parent']))
        ], 201);
    }

    /**
     * Display the specified configuration item.
     */
    public function show(ConfigurationItem $configurationItem): ConfigurationItemResource
    {
        return new ConfigurationItemResource(
            $configurationItem->load([
                'owner',
                'department',
                'parent',
                'children',
                'incidents',
                'changes',
                'relatedItems',
                'documents'
            ])
        );
    }

    /**
     * Update the specified configuration item.
     */
    public function update(UpdateConfigurationItemRequest $request, ConfigurationItem $configurationItem): JsonResponse
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated, $configurationItem) {
            $configurationItem->update($validated);
            
            // If relationships are provided, sync them
            if (isset($validated['related_items'])) {
                $configurationItem->relatedItems()->sync($validated['related_items']);
            }
            
            // Log the update
            activity()
                ->performedOn($configurationItem)
                ->causedBy(auth()->user())
                ->withProperties(['changes' => $configurationItem->getChanges()])
                ->log('Configuration item updated');
        });

        return response()->json([
            'message' => 'Configuration item updated successfully',
            'data' => new ConfigurationItemResource($configurationItem->fresh(['owner', 'department', 'parent']))
        ]);
    }

    /**
     * Remove the specified configuration item.
     */
    public function destroy(ConfigurationItem $configurationItem): JsonResponse
    {
        DB::transaction(function () use ($configurationItem) {
            // Log the deletion
            activity()
                ->performedOn($configurationItem)
                ->causedBy(auth()->user())
                ->log('Configuration item deleted');
            
            $configurationItem->delete();
        });

        return response()->json([
            'message' => 'Configuration item deleted successfully'
        ], 204);
    }

    /**
     * Get configuration item types.
     */
    public function types(): JsonResponse
    {
        $types = Cache::remember('config_item_types', 3600, function () {
            return [
                ['value' => 'server', 'label' => 'Server'],
                ['value' => 'workstation', 'label' => 'Workstation'],
                ['value' => 'laptop', 'label' => 'Laptop'],
                ['value' => 'network_device', 'label' => 'Network Device'],
                ['value' => 'printer', 'label' => 'Printer'],
                ['value' => 'software', 'label' => 'Software'],
                ['value' => 'license', 'label' => 'License'],
                ['value' => 'virtual_machine', 'label' => 'Virtual Machine'],
                ['value' => 'mobile_device', 'label' => 'Mobile Device'],
                ['value' => 'other', 'label' => 'Other']
            ];
        });

        return response()->json(['data' => $types]);
    }

    /**
     * Get configuration item statuses.
     */
    public function statuses(): JsonResponse
    {
        $statuses = [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
            ['value' => 'maintenance', 'label' => 'Under Maintenance'],
            ['value' => 'retired', 'label' => 'Retired'],
            ['value' => 'disposed', 'label' => 'Disposed']
        ];

        return response()->json(['data' => $statuses]);
    }

    /**
     * Get relationships for a configuration item.
     */
    public function relationships(ConfigurationItem $configurationItem): JsonResponse
    {
        $relationships = [
            'parent' => $configurationItem->parent ? new ConfigurationItemResource($configurationItem->parent) : null,
            'children' => ConfigurationItemResource::collection($configurationItem->children),
            'related_items' => ConfigurationItemResource::collection($configurationItem->relatedItems),
            'dependencies' => ConfigurationItemResource::collection($configurationItem->dependencies),
            'dependents' => ConfigurationItemResource::collection($configurationItem->dependents)
        ];

        return response()->json(['data' => $relationships]);
    }

    /**
     * Get impact analysis for a configuration item.
     */
    public function impact(ConfigurationItem $configurationItem): JsonResponse
    {
        // Calculate impact based on relationships and dependencies
        $impact = [
            'direct_dependencies' => $configurationItem->dependencies()->count(),
            'direct_dependents' => $configurationItem->dependents()->count(),
            'total_affected_items' => 0,
            'affected_services' => [],
            'affected_users' => [],
            'risk_level' => 'low'
        ];

        // Get all affected items recursively
        $affectedItems = collect();
        $this->getAffectedItems($configurationItem, $affectedItems);
        
        $impact['total_affected_items'] = $affectedItems->count();
        
        // Determine risk level
        if ($impact['total_affected_items'] > 50) {
            $impact['risk_level'] = 'critical';
        } elseif ($impact['total_affected_items'] > 20) {
            $impact['risk_level'] = 'high';
        } elseif ($impact['total_affected_items'] > 5) {
            $impact['risk_level'] = 'medium';
        }

        return response()->json(['data' => $impact]);
    }

    /**
     * Get maintenance history for a configuration item.
     */
    public function maintenanceHistory(ConfigurationItem $configurationItem): JsonResponse
    {
        $history = $configurationItem->maintenanceRecords()
            ->with('performedBy')
            ->orderBy('performed_at', 'desc')
            ->get();

        return response()->json(['data' => $history]);
    }

    /**
     * Export configuration items.
     */
    public function export(Request $request): JsonResponse
    {
        // This would typically generate a CSV or Excel file
        // For now, return a success response
        return response()->json([
            'message' => 'Export initiated',
            'download_url' => '/api/v1/exports/configuration-items/' . uniqid()
        ]);
    }

    /**
     * Bulk update configuration items.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:configuration_items,id',
            'updates' => 'required|array',
            'updates.status' => 'sometimes|string|in:active,inactive,maintenance,retired,disposed',
            'updates.location' => 'sometimes|string',
            'updates.department_id' => 'sometimes|exists:departments,id'
        ]);

        $updated = DB::transaction(function () use ($validated) {
            return ConfigurationItem::whereIn('id', $validated['item_ids'])
                ->update($validated['updates']);
        });

        return response()->json([
            'message' => "Successfully updated {$updated} configuration items"
        ]);
    }

    /**
     * Helper method to recursively get affected items.
     */
    private function getAffectedItems(ConfigurationItem $item, &$collection, $depth = 0)
    {
        if ($depth > 5) return; // Prevent infinite recursion
        
        foreach ($item->dependents as $dependent) {
            if (!$collection->contains('id', $dependent->id)) {
                $collection->push($dependent);
                $this->getAffectedItems($dependent, $collection, $depth + 1);
            }
        }
    }
}