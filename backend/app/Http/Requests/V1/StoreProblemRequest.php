<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProblemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-problems');
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
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category' => ['required', Rule::in(['hardware', 'software', 'network', 'database', 'security', 'process', 'other'])],
            'subcategory' => ['required', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'impact' => ['required', Rule::in(['low', 'medium', 'high', 'enterprise'])],
            'urgency' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'affected_services' => ['required', 'array', 'min:1'],
            'affected_services.*' => ['string', 'max:255'],
            'affected_cis' => ['nullable', 'array'],
            'affected_cis.*' => ['integer', 'exists:configuration_items,id'],
            'symptoms' => ['required', 'array', 'min:1'],
            'symptoms.*' => ['string', 'min:10', 'max:500'],
            'known_errors' => ['nullable', 'array'],
            'known_errors.*' => ['string', 'min:10', 'max:1000'],
            'initial_diagnosis' => ['nullable', 'string', 'min:20', 'max:2000'],
            'business_impact' => ['required', 'string', 'min:20', 'max:1000'],
            'reported_by' => ['required', 'integer', 'exists:users,id'],
            'detected_date' => ['required', 'date', 'before_or_equal:today'],
            'occurrence_count' => ['nullable', 'integer', 'min:1'],
            'related_incidents' => ['nullable', 'array'],
            'related_incidents.*' => ['integer', 'exists:incidents,id'],
            'related_changes' => ['nullable', 'array'],
            'related_changes.*' => ['integer', 'exists:changes,id'],
            'investigation_team' => ['nullable', 'array'],
            'investigation_team.*' => ['integer', 'exists:users,id'],
            'problem_owner' => ['required', 'integer', 'exists:users,id'],
            'target_resolution_date' => ['nullable', 'date', 'after:today'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,log,txt', 'max:20480'],
            'notification_list' => ['nullable', 'array'],
            'notification_list.*' => ['email'],
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
            'title.required' => 'Problem title is required.',
            'title.max' => 'Problem title cannot exceed 255 characters.',
            'description.required' => 'Problem description is required.',
            'description.min' => 'Description must be at least 20 characters long.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'category.required' => 'Problem category must be specified.',
            'category.in' => 'Invalid problem category selected.',
            'subcategory.required' => 'Problem subcategory is required.',
            'priority.required' => 'Priority level must be specified.',
            'priority.in' => 'Invalid priority level selected.',
            'impact.required' => 'Impact assessment is required.',
            'impact.in' => 'Invalid impact level selected.',
            'urgency.required' => 'Urgency level must be specified.',
            'urgency.in' => 'Invalid urgency level selected.',
            'affected_services.required' => 'At least one affected service must be specified.',
            'affected_services.min' => 'At least one affected service must be specified.',
            'affected_cis.*.exists' => 'One or more configuration items do not exist.',
            'symptoms.required' => 'At least one symptom must be described.',
            'symptoms.min' => 'At least one symptom must be described.',
            'symptoms.*.min' => 'Each symptom description must be at least 10 characters.',
            'symptoms.*.max' => 'Each symptom description cannot exceed 500 characters.',
            'known_errors.*.min' => 'Known error description must be at least 10 characters.',
            'initial_diagnosis.min' => 'Initial diagnosis must be at least 20 characters.',
            'business_impact.required' => 'Business impact description is required.',
            'business_impact.min' => 'Business impact must be at least 20 characters.',
            'reported_by.required' => 'Reporter information is required.',
            'reported_by.exists' => 'Selected reporter does not exist.',
            'detected_date.required' => 'Problem detection date is required.',
            'detected_date.before_or_equal' => 'Detection date cannot be in the future.',
            'occurrence_count.min' => 'Occurrence count must be at least 1.',
            'related_incidents.*.exists' => 'One or more related incidents do not exist.',
            'related_changes.*.exists' => 'One or more related changes do not exist.',
            'investigation_team.*.exists' => 'One or more investigation team members do not exist.',
            'problem_owner.required' => 'Problem owner must be assigned.',
            'problem_owner.exists' => 'Selected problem owner does not exist.',
            'target_resolution_date.after' => 'Target resolution date must be in the future.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Excel, Images, Log files, Text.',
            'attachments.*.max' => 'Each attachment must not exceed 20MB.',
            'notification_list.*.email' => 'Invalid email address in notification list.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default reported_by to current user if not provided
        if (!$this->has('reported_by')) {
            $this->merge([
                'reported_by' => $this->user()->id,
            ]);
        }

        // Set default occurrence count
        if (!$this->has('occurrence_count')) {
            $this->merge([
                'occurrence_count' => 1,
            ]);
        }
    }
}