<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $change = $this->route('change');
        return $this->user()->can('update-changes') && 
               in_array($change->status, ['draft', 'pending', 'under_review']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10'],
            'type' => ['sometimes', Rule::in(['standard', 'normal', 'emergency'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'impact' => ['sometimes', Rule::in(['low', 'medium', 'high', 'extensive'])],
            'risk_level' => ['sometimes', Rule::in(['low', 'medium', 'high', 'very_high'])],
            'category' => ['sometimes', 'string', 'max:100'],
            'affected_systems' => ['sometimes', 'array', 'min:1'],
            'affected_systems.*' => ['string', 'max:255'],
            'implementation_plan' => ['sometimes', 'string', 'min:50'],
            'rollback_plan' => ['sometimes', 'string', 'min:50'],
            'test_plan' => ['sometimes', 'string', 'min:30'],
            'scheduled_start' => ['sometimes', 'date', 'after:now'],
            'scheduled_end' => ['sometimes', 'date', 'after:scheduled_start'],
            'justification' => ['sometimes', 'string', 'min:20'],
            'expected_downtime' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'pending', 'under_review', 'approved', 'rejected', 'scheduled', 'in_progress', 'completed', 'failed', 'cancelled'])],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,txt', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'exists:attachments,id'],
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
            'title.max' => 'The change title cannot exceed 255 characters.',
            'description.min' => 'The description must be at least 10 characters long.',
            'type.in' => 'Invalid change type selected.',
            'priority.in' => 'Invalid priority level selected.',
            'impact.in' => 'Invalid impact level selected.',
            'risk_level.in' => 'Invalid risk level selected.',
            'affected_systems.min' => 'At least one affected system must be specified.',
            'implementation_plan.min' => 'Implementation plan must be at least 50 characters long.',
            'rollback_plan.min' => 'Rollback plan must be at least 50 characters long.',
            'test_plan.min' => 'Test plan must be at least 30 characters long.',
            'scheduled_start.after' => 'Scheduled start time must be in the future.',
            'scheduled_end.after' => 'Scheduled end time must be after the start time.',
            'justification.min' => 'Justification must be at least 20 characters long.',
            'expected_downtime.integer' => 'Expected downtime must be a number in minutes.',
            'status.in' => 'Invalid status value.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.mimes' => 'Only PDF, Word, Excel, and text files are allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'remove_attachments.*.exists' => 'One or more attachments to remove were not found.',
        ];
    }
}