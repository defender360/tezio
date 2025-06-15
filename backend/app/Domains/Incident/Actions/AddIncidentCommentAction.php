<?php

namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\DTOs\IncidentCommentData;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Models\IncidentComment;
use App\Domains\Incident\Models\IncidentHistory;

class AddIncidentCommentAction extends Action
{
    public function execute(Incident $incident, IncidentCommentData $data): IncidentComment
    {
        $comment = new IncidentComment($data->toArray());
        $comment->incident_id = $incident->id;
        $comment->user_id = auth()->id();
        $comment->save();
        
        // Record in history
        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => auth()->id(),
            'action' => 'commented',
            'metadata' => [
                'comment_id' => $comment->id,
                'is_internal' => $comment->is_internal,
            ],
        ]);
        
        return $comment->fresh('user');
    }
}