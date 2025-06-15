<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');
        return $this->user()->can('update-service-tasks') || 
               $this->user()->id === $task->assigned_to;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $task = $this->route('task');
        $currentStatus = $task->status ?? 'new';
        
        $rules = [
            'status' => ['required', Rule::in($this->getAllowedStatuses($currentStatus))],
            'comments' => ['required', 'string', 'min:10', 'max:1000'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'blockers' => ['nullable', 'array'],
            'blockers.*' => ['required', 'array'],
            'blockers.*.type' => ['required', Rule::in(['technical', 'resource', 'dependency', 'approval', 'other'])],
            'blockers.*.description' => ['required', 'string', 'min:10', 'max:500'],
            'blockers.*.severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'checklist_updates' => ['nullable', 'array'],
            'checklist_updates.*' => ['required', 'array'],
            'checklist_updates.*.id' => ['required', 'integer', 'exists:task_checklist_items,id'],
            'checklist_updates.*.completed' => ['required', 'boolean'],
            'checklist_updates.*.notes' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,txt', 'max:5120'],
            'time_entries' => ['nullable', 'array'],
            'time_entries.*' => ['required', 'array'],
            'time_entries.*.date' => ['required', 'date', 'before_or_equal:today'],
            'time_entries.*.hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'time_entries.*.description' => ['required', 'string', 'min:10', 'max:500'],
            'escalate' => ['nullable', 'boolean'],
            'escalation_reason' => ['required_if:escalate,true', 'nullable', 'string', 'min:20', 'max:500'],
            'reassign_to' => ['nullable', 'integer', 'exists:users,id', 'different:' . $task->assigned_to],
            'reassignment_reason' => ['required_with:reassign_to', 'nullable', 'string', 'min:10', 'max:500'],
        ];

        // Additional rules based on status
        if ($this->input('status') === 'completed') {
            $rules['resolution_summary'] = ['required', 'string', 'min:20', 'max:1000'];
            $rules['completion_checklist_confirmed'] = ['required', 'boolean', 'accepted'];
            $rules['completion_percentage'] = ['required', 'integer', 'min:100', 'max:100'];
        }

        if ($this->input('status') === 'cancelled') {
            $rules['cancellation_reason'] = ['required', 'string', 'min:20', 'max:500'];
        }

        if ($this->input('status') === 'on_hold') {
            $rules['hold_reason'] = ['required', 'string', 'min:10', 'max:500'];
            $rules['expected_resume_date'] = ['nullable', 'date', 'after:today'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Task status is required.',
            'status.in' => 'Invalid status transition. This status change is not allowed.',
            'comments.required' => 'Status update comments are required.',
            'comments.min' => 'Comments must be at least 10 characters long.',
            'actual_hours.numeric' => 'Actual hours must be a valid number.',
            'actual_hours.max' => 'Actual hours cannot exceed 999.99.',
            'completion_percentage.required' => 'Completion percentage is required.',
            'completion_percentage.min' => 'Completion percentage cannot be negative.',
            'completion_percentage.max' => 'Completion percentage cannot exceed 100%.',
            'blockers.*.type.required' => 'Blocker type is required.',
            'blockers.*.type.in' => 'Invalid blocker type.',
            'blockers.*.description.required' => 'Blocker description is required.',
            'blockers.*.description.min' => 'Blocker description must be at least 10 characters.',
            'blockers.*.severity.required' => 'Blocker severity is required.',
            'blockers.*.severity.in' => 'Invalid blocker severity.',
            'checklist_updates.*.id.required' => 'Checklist item ID is required.',
            'checklist_updates.*.id.exists' => 'Checklist item not found.',
            'checklist_updates.*.completed.required' => 'Checklist item completion status is required.',
            'attachments.max' => 'Maximum 3 attachments allowed per update.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Images, Text.',
            'attachments.*.max' => 'Each attachment must not exceed 5MB.',
            'time_entries.*.date.required' => 'Time entry date is required.',
            'time_entries.*.date.before_or_equal' => 'Time entry date cannot be in the future.',
            'time_entries.*.hours.required' => 'Time entry hours are required.',
            'time_entries.*.hours.min' => 'Minimum time entry is 0.25 hours (15 minutes).',
            'time_entries.*.hours.max' => 'Maximum time entry is 24 hours per day.',
            'time_entries.*.description.required' => 'Time entry description is required.',
            'time_entries.*.description.min' => 'Time entry description must be at least 10 characters.',
            'escalation_reason.required_if' => 'Escalation reason is required when escalating the task.',
            'escalation_reason.min' => 'Escalation reason must be at least 20 characters.',
            'reassign_to.exists' => 'Selected assignee does not exist.',
            'reassign_to.different' => 'Cannot reassign task to the same person.',
            'reassignment_reason.required_with' => 'Reassignment reason is required.',
            'reassignment_reason.min' => 'Reassignment reason must be at least 10 characters.',
            'resolution_summary.required' => 'Resolution summary is required when completing the task.',
            'resolution_summary.min' => 'Resolution summary must be at least 20 characters.',
            'completion_checklist_confirmed.required' => 'Please confirm all checklist items are completed.',
            'completion_checklist_confirmed.accepted' => 'You must confirm all checklist items are completed.',
            'completion_percentage.min' => 'Task must be 100% complete to mark as completed.',
            'cancellation_reason.required' => 'Cancellation reason is required.',
            'cancellation_reason.min' => 'Cancellation reason must be at least 20 characters.',
            'hold_reason.required' => 'Hold reason is required when putting task on hold.',
            'hold_reason.min' => 'Hold reason must be at least 10 characters.',
            'expected_resume_date.after' => 'Expected resume date must be in the future.',
        ];
    }

    /**
     * Get allowed status transitions based on current status.
     *
     * @param string $currentStatus
     * @return array
     */
    protected function getAllowedStatuses(string $currentStatus): array
    {
        $transitions = [
            'new' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'on_hold', 'cancelled'],
            'in_progress' => ['completed', 'on_hold', 'blocked', 'cancelled'],
            'on_hold' => ['in_progress', 'cancelled'],
            'blocked' => ['in_progress', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('escalate')) {
            $this->merge([
                'escalate' => filter_var($this->escalate, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('completion_checklist_confirmed')) {
            $this->merge([
                'completion_checklist_confirmed' => filter_var($this->completion_checklist_confirmed, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('checklist_updates')) {
            $updates = $this->checklist_updates;
            foreach ($updates as $key => $update) {
                if (isset($update['completed'])) {
                    $updates[$key]['completed'] = filter_var($update['completed'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['checklist_updates' => $updates]);
        }
    }
}