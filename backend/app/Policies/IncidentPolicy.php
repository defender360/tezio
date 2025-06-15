<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IncidentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view incidents list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Incident $incident): bool
    {
        // Users can view public incidents
        if (!$incident->is_internal) {
            return true;
        }

        // Internal incidents can be viewed by staff and admins
        return $user->hasAnyRole(['admin', 'staff', 'department_head']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can report incidents
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Incident $incident): bool
    {
        // Admins can always update
        if ($user->hasRole('admin')) {
            return true;
        }

        // Department heads can update incidents in their department
        if ($user->hasRole('department_head') && $incident->department_id === $user->department_id) {
            return true;
        }

        // Staff assigned to the incident can update it
        if ($user->hasRole('staff') && $incident->assigned_to === $user->id) {
            return true;
        }

        // Original reporter can update if incident is still pending
        if ($incident->reporter_id === $user->id && $incident->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Incident $incident): bool
    {
        // Only admins can delete incidents
        if ($user->hasRole('admin')) {
            return true;
        }

        // Department heads can delete incidents in their department if not resolved
        if ($user->hasRole('department_head') && 
            $incident->department_id === $user->department_id && 
            $incident->status !== 'resolved') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Incident $incident): bool
    {
        // Only admins can restore deleted incidents
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Incident $incident): bool
    {
        // Only admins can permanently delete incidents
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign the incident to someone.
     */
    public function assign(User $user, Incident $incident): bool
    {
        // Admins can always assign
        if ($user->hasRole('admin')) {
            return true;
        }

        // Department heads can assign incidents in their department
        if ($user->hasRole('department_head') && $incident->department_id === $user->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can change the incident status.
     */
    public function changeStatus(User $user, Incident $incident): bool
    {
        // Admins can always change status
        if ($user->hasRole('admin')) {
            return true;
        }

        // Department heads can change status for their department's incidents
        if ($user->hasRole('department_head') && $incident->department_id === $user->department_id) {
            return true;
        }

        // Assigned staff can change status
        if ($user->hasRole('staff') && $incident->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view internal notes.
     */
    public function viewInternalNotes(User $user, Incident $incident): bool
    {
        return $user->hasAnyRole(['admin', 'staff', 'department_head']);
    }

    /**
     * Determine whether the user can view financial data.
     */
    public function viewFinancialData(User $user, Incident $incident): bool
    {
        // Admins can always view financial data
        if ($user->hasRole('admin')) {
            return true;
        }

        // Department heads can view financial data for their department
        if ($user->hasRole('department_head') && $incident->department_id === $user->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can add comments to the incident.
     */
    public function comment(User $user, Incident $incident): bool
    {
        // If user can view the incident, they can comment on it
        return $this->view($user, $incident);
    }

    /**
     * Determine whether the user can add internal comments.
     */
    public function addInternalComment(User $user, Incident $incident): bool
    {
        return $user->hasAnyRole(['admin', 'staff', 'department_head']);
    }

    /**
     * Determine whether the user can escalate the incident.
     */
    public function escalate(User $user, Incident $incident): bool
    {
        // Admins and department heads can escalate
        if ($user->hasAnyRole(['admin', 'department_head'])) {
            return true;
        }

        // Assigned staff can escalate
        if ($user->hasRole('staff') && $incident->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate the incident.
     */
    public function duplicate(User $user, Incident $incident): bool
    {
        return $user->hasAnyRole(['admin', 'staff', 'department_head']);
    }

    /**
     * Determine whether the user can export incident data.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'department_head']);
    }

    /**
     * Determine whether the user can bulk update incidents.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'department_head']);
    }
}