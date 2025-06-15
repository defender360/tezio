<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProblemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $problem = $this->route('problem');
        return $this->user()->can('update-problems') || 
               $this->user()->id === $problem->problem_owner;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $problem = $this->route('problem');
        
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:20', 'max:5000'],
            'category' => ['sometimes', Rule::in(['hardware', 'software', 'network', 'database', 'security', 'process', 'other'])],
            'subcategory' => ['sometimes', 'string', 'max:100'],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'impact' => ['sometimes', Rule::in(['low', 'medium', 'high', 'enterprise'])],
            'urgency' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['sometimes', Rule::in(['new', 'assigned', 'investigating', 'identified', 'resolved', 'closed', 'cancelled'])],
            'affected_services' => ['sometimes', 'array', 'min:1'],
            'affected_services.*' => ['string', 'max:255'],
            'affected_cis' => ['nullable', 'array'],
            'affected_cis.*' => ['integer', 'exists:configuration_items,id'],
            'symptoms' => ['sometimes', 'array', 'min:1'],
            'symptoms.*' => ['string', 'min:10', 'max:500'],
            'known_errors' => ['nullable', 'array'],
            'known_errors.*' => ['string', 'min:10', 'max:1000'],
            'business_impact' => ['sometimes', 'string', 'min:20', 'max:1000'],
            'occurrence_count' => ['nullable', 'integer', 'min:1'],
            'investigation_team' => ['nullable', 'array'],
            'investigation_team.*' => ['integer', 'exists:users,id'],
            'problem_owner' => ['sometimes', 'integer', 'exists:users,id'],
            'target_resolution_date' => ['nullable', 'date', 'after:today'],
            'actual_resolution_date' => ['nullable', 'date', 'before_or_equal:today'],
            'root_cause' => ['required_if:status,identified,resolved', 'nullable', 'string', 'min:50', 'max:3000'],
            'resolution' => ['required_if:status,resolved', 'nullable', 'string', 'min:50', 'max:3000'],
            'lessons_learned' => ['nullable', 'string', 'min:20', 'max:2000'],
            'preventive_actions' => ['nullable', 'array'],
            'preventive_actions.*' => ['string', 'min:10', 'max:500'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,log,txt', 'max:20480'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'exists:attachments,id'],
            'notification_list' => ['nullable', 'array'],
            'notification_list.*' => ['email'],
            'closure_notes' => ['required_if:status,closed', 'nullable', 'string', 'min:20', 'max:1000'],
            'closure_code' => ['required_if:status,closed', 'nullable', Rule::in(['resolved', 'workaround', 'cancelled', 'duplicate'])],
        ];

        // Restrict certain fields based on status
        if (in_array($problem->status, ['closed', 'cancelled'])) {
            $restrictedFields = ['title', 'category', 'subcategory', 'affected_services', 'symptoms'];
            foreach ($restrictedFields as $field) {
                unset($rules[$field]);
            }
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
            'title.max' => 'Problem title cannot exceed 255 characters.',
            'description.min' => 'Description must be at least 20 characters long.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'category.in' => 'Invalid problem category selected.',
            'priority.in' => 'Invalid priority level selected.',
            'impact.in' => 'Invalid impact level selected.',
            'urgency.in' => 'Invalid urgency level selected.',
            'status.in' => 'Invalid status value.',
            'affected_services.min' => 'At least one affected service must be specified.',
            'affected_cis.*.exists' => 'One or more configuration items do not exist.',
            'symptoms.min' => 'At least one symptom must be described.',
            'symptoms.*.min' => 'Each symptom description must be at least 10 characters.',
            'symptoms.*.max' => 'Each symptom description cannot exceed 500 characters.',
            'known_errors.*.min' => 'Known error description must be at least 10 characters.',
            'business_impact.min' => 'Business impact must be at least 20 characters.',
            'occurrence_count.min' => 'Occurrence count must be at least 1.',
            'investigation_team.*.exists' => 'One or more investigation team members do not exist.',
            'problem_owner.exists' => 'Selected problem owner does not exist.',
            'target_resolution_date.after' => 'Target resolution date must be in the future.',
            'actual_resolution_date.before_or_equal' => 'Actual resolution date cannot be in the future.',
            'root_cause.required_if' => 'Root cause analysis is required when problem is identified or resolved.',
            'root_cause.min' => 'Root cause analysis must be at least 50 characters.',
            'resolution.required_if' => 'Resolution details are required when problem is resolved.',
            'resolution.min' => 'Resolution details must be at least 50 characters.',
            'lessons_learned.min' => 'Lessons learned must be at least 20 characters.',
            'preventive_actions.*.min' => 'Each preventive action must be at least 10 characters.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Excel, Images, Log files, Text.',
            'attachments.*.max' => 'Each attachment must not exceed 20MB.',
            'remove_attachments.*.exists' => 'One or more attachments to remove were not found.',
            'notification_list.*.email' => 'Invalid email address in notification list.',
            'closure_notes.required_if' => 'Closure notes are required when closing the problem.',
            'closure_notes.min' => 'Closure notes must be at least 20 characters.',
            'closure_code.required_if' => 'Closure code is required when closing the problem.',
            'closure_code.in' => 'Invalid closure code selected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'affected_cis' => 'affected configuration items',
            'target_resolution_date' => 'target resolution date',
            'actual_resolution_date' => 'actual resolution date',
        ];
    }
}