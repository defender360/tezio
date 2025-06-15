<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-changes');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'type' => ['required', Rule::in(['standard', 'normal', 'emergency'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'impact' => ['required', Rule::in(['low', 'medium', 'high', 'extensive'])],
            'risk_level' => ['required', Rule::in(['low', 'medium', 'high', 'very_high'])],
            'requested_by' => ['required', 'exists:users,id'],
            'category' => ['required', 'string', 'max:100'],
            'affected_systems' => ['required', 'array', 'min:1'],
            'affected_systems.*' => ['string', 'max:255'],
            'implementation_plan' => ['required', 'string', 'min:50'],
            'rollback_plan' => ['required', 'string', 'min:50'],
            'test_plan' => ['required', 'string', 'min:30'],
            'scheduled_start' => ['required', 'date', 'after:now'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
            'justification' => ['required', 'string', 'min:20'],
            'expected_downtime' => ['nullable', 'integer', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,txt', 'max:10240'],
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
            'title.required' => 'A change title is required.',
            'title.max' => 'The change title cannot exceed 255 characters.',
            'description.required' => 'A detailed description of the change is required.',
            'description.min' => 'The description must be at least 10 characters long.',
            'type.required' => 'Please select a change type.',
            'type.in' => 'Invalid change type selected.',
            'priority.required' => 'Priority level must be specified.',
            'priority.in' => 'Invalid priority level selected.',
            'impact.required' => 'Impact assessment is required.',
            'impact.in' => 'Invalid impact level selected.',
            'risk_level.required' => 'Risk level assessment is required.',
            'risk_level.in' => 'Invalid risk level selected.',
            'requested_by.required' => 'Requester information is required.',
            'requested_by.exists' => 'The specified requester does not exist.',
            'category.required' => 'Change category is required.',
            'affected_systems.required' => 'At least one affected system must be specified.',
            'affected_systems.min' => 'At least one affected system must be specified.',
            'implementation_plan.required' => 'A detailed implementation plan is required.',
            'implementation_plan.min' => 'Implementation plan must be at least 50 characters long.',
            'rollback_plan.required' => 'A rollback plan is required for all changes.',
            'rollback_plan.min' => 'Rollback plan must be at least 50 characters long.',
            'test_plan.required' => 'A test plan is required.',
            'test_plan.min' => 'Test plan must be at least 30 characters long.',
            'scheduled_start.required' => 'Scheduled start time is required.',
            'scheduled_start.after' => 'Scheduled start time must be in the future.',
            'scheduled_end.required' => 'Scheduled end time is required.',
            'scheduled_end.after' => 'Scheduled end time must be after the start time.',
            'justification.required' => 'Business justification is required.',
            'justification.min' => 'Justification must be at least 20 characters long.',
            'expected_downtime.integer' => 'Expected downtime must be a number in minutes.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.mimes' => 'Only PDF, Word, Excel, and text files are allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
        ];
    }
}