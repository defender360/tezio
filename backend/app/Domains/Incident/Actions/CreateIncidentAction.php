<?php

namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Models\IncidentHistory;
use Carbon\Carbon;

class CreateIncidentAction extends Action
{
    public function execute(CreateIncidentData $data): Incident
    {
        $incident = new Incident($data->toArray());
        
        // Set created_by to current user
        $incident->created_by = auth()->id();
        
        // Set initial status
        $incident->status = Incident::STATUS_OPEN;
        
        // Calculate SLA deadline based on priority
        $incident->sla_deadline = Carbon::now()->addHours($incident->getSlaHours());
        
        $incident->save();
        
        // Record creation in history
        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'metadata' => [
                'initial_data' => $data->toArray(),
            ],
        ]);
        
        return $incident->fresh(['creator', 'assignee']);
    }
}