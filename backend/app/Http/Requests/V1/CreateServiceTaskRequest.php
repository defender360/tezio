<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-service-tasks');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_request_id' => ['required', 'integer', 'exists:service_requests,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'task_type' => ['required', Rule::in(['approval', 'fulfillment', 'procurement', 'configuration', 'installation', 'verification'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'assigned_group' => ['nullable', 'string', 'max:100'],
            'estimated_hours' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'due_date' => ['required', 'date', 'after:now'],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*' => ['integer', 'exists:service_tasks,id'],
            'checklist_items' => ['nullable', 'array', 'max:20'],
            'checklist_items.*' => ['required', 'array'],
            'checklist_items.*.title' => ['required', 'string', 'max:255'],
            'checklist_items.*.description' => ['nullable', 'string', 'max:500'],
            'checklist_items.*.is_required' => ['required', 'boolean'],
            'checklist_items.*.order' => ['required', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'acceptance_criteria' => ['required', 'string', 'min:20', 'max:1000'],
            'skills_required' => ['nullable', 'array', 'max:10'],
            'skills_required.*' => ['string', 'max:50'],
            'tools_required' => ['nullable', 'array', 'max:10'],
            'tools_required.*' => ['string', 'max:100'],
            'escalation_time' => ['nullable', 'integer', 'min:30', 'max:10080'], // 30 minutes to 7 days in minutes
            'escalation_to' => ['required_with:escalation_time', 'nullable', 'integer', 'exists:users,id'],
            'requires_approval' => ['required', 'boolean'],
            'approvers' => ['required_if:requires_approval,true', 'nullable', 'array', 'min:1'],
            'approvers.*' => ['integer', 'exists:users,id'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.on_creation' => ['nullable', 'boolean'],
            'notification_settings.on_update' => ['nullable', 'boolean'],
            'notification_settings.on_completion' => ['nullable', 'boolean'],
            'notification_settings.on_overdue' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,txt', 'max:5120'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_request_id.required' => 'Service request ID is required.',
            'service_request_id.exists' => 'The specified service request does not exist.',
            'title.required' => 'Task title is required.',
            'title.max' => 'Task title cannot exceed 255 characters.',
            'description.required' => 'Task description is required.',
            'description.min' => 'Description must be at least 10 characters long.',
            'task_type.required' => 'Task type must be specified.',
            'task_type.in' => 'Invalid task type selected.',
            'priority.required' => 'Task priority must be specified.',
            'priority.in' => 'Invalid priority level selected.',
            'assigned_to.required' => 'Task must be assigned to someone.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'estimated_hours.required' => 'Estimated hours are required.',
            'estimated_hours.min' => 'Estimated hours must be at least 0.25 (15 minutes).',
            'estimated_hours.max' => 'Estimated hours cannot exceed 999.99.',
            'due_date.required' => 'Task due date is required.',
            'due_date.after' => 'Due date must be in the future.',
            'dependencies.*.exists' => 'One or more task dependencies do not exist.',
            'checklist_items.max' => 'Maximum 20 checklist items allowed.',
            'checklist_items.*.title.required' => 'Checklist item title is required.',
            'checklist_items.*.is_required.required' => 'Please specify if checklist item is required.',
            'checklist_items.*.order.required' => 'Checklist item order is required.',
            'acceptance_criteria.required' => 'Task acceptance criteria are required.',
            'acceptance_criteria.min' => 'Acceptance criteria must be at least 20 characters.',
            'skills_required.max' => 'Maximum 10 required skills can be specified.',
            'tools_required.max' => 'Maximum 10 required tools can be specified.',
            'escalation_time.min' => 'Escalation time must be at least 30 minutes.',
            'escalation_time.max' => 'Escalation time cannot exceed 7 days.',
            'escalation_to.required_with' => 'Escalation recipient must be specified when escalation time is set.',
            'escalation_to.exists' => 'Selected escalation recipient does not exist.',
            'requires_approval.required' => 'Please specify if task requires approval.',
            'approvers.required_if' => 'At least one approver must be specified when approval is required.',
            'approvers.min' => 'At least one approver must be specified.',
            'approvers.*.exists' => 'One or more selected approvers do not exist.',
            'attachments.max' => 'Maximum 3 attachments allowed.',
            'attachments.*.mimes' => 'Only PDF, Word, and text files are allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('requires_approval')) {
            $this->merge([
                'requires_approval' => filter_var($this->requires_approval, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('checklist_items')) {
            $checklistItems = $this->checklist_items;
            foreach ($checklistItems as $key => $item) {
                if (isset($item['is_required'])) {
                    $checklistItems[$key]['is_required'] = filter_var($item['is_required'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['checklist_items' => $checklistItems]);
        }

        if ($this->has('notification_settings')) {
            $settings = $this->notification_settings;
            foreach (['on_creation', 'on_update', 'on_completion', 'on_overdue'] as $setting) {
                if (isset($settings[$setting])) {
                    $settings[$setting] = filter_var($settings[$setting], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['notification_settings' => $settings]);
        }
    }
}