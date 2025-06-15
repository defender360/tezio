<?php

namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\DTOs\UpdateIncidentData;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Models\IncidentHistory;
use Carbon\Carbon;

class UpdateIncidentAction extends Action
{
    public function execute(Incident $incident, UpdateIncidentData $data): Incident
    {
        $changes = [];
        $dataArray = $data->toArray();
        
        foreach ($dataArray as $field => $value) {
            if ($value !== null && $incident->$field !== $value) {
                $changes[$field] = [
                    'old' => $incident->$field,
                    'new' => $value,
                ];
                $incident->$field = $value;
            }
        }
        
        // Handle status changes
        if (isset($changes['status'])) {
            if ($changes['status']['new'] === Incident::STATUS_RESOLVED && !$incident->resolved_at) {
                $incident->resolved_at = Carbon::now();
            } elseif ($changes['status']['new'] === Incident::STATUS_CLOSED && !$incident->closed_at) {
                $incident->closed_at = Carbon::now();
            }
        }
        
        $incident->save();
        
        // Record each change in history
        foreach ($changes as $field => $change) {
            IncidentHistory::create([
                'incident_id' => $incident->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'field' => $field,
                'old_value' => is_array($change['old']) ? json_encode($change['old']) : $change['old'],
                'new_value' => is_array($change['new']) ? json_encode($change['new']) : $change['new'],
            ]);
        }
        
        return $incident->fresh(['creator', 'assignee']);
    }
}