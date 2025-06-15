<?php

namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Models\IncidentHistory;
use Carbon\Carbon;

class ResolveIncidentAction extends Action
{
    public function execute(Incident $incident, string $resolutionNotes): Incident
    {
        $incident->status = Incident::STATUS_RESOLVED;
        $incident->resolved_at = Carbon::now();
        $incident->resolution_notes = $resolutionNotes;
        $incident->save();
        
        // Record resolution in history
        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => auth()->id(),
            'action' => 'resolved',
            'metadata' => [
                'resolution_notes' => $resolutionNotes,
                'resolved_at' => $incident->resolved_at->toIso8601String(),
            ],
        ]);
        
        return $incident;
    }
}