<?php

namespace App\Http\Requests;

use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');
        
        // Check if user can update this specific incident
        return $incident && $this->user() && $this->user()->can('update', $incident);
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
            'description' => ['sometimes', 'string'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Incident::STATUS_OPEN,
                    Incident::STATUS_IN_PROGRESS,
                    Incident::STATUS_RESOLVED,
                    Incident::STATUS_CLOSED
                ])
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::in([
                    Incident::PRIORITY_LOW,
                    Incident::PRIORITY_MEDIUM,
                    Incident::PRIORITY_HIGH,
                    Incident::PRIORITY_CRITICAL
                ])
            ],
            'impact' => [
                'sometimes',
                'string',
                Rule::in([
                    Incident::IMPACT_LOW,
                    Incident::IMPACT_MEDIUM,
                    Incident::IMPACT_HIGH,
                    Incident::IMPACT_ENTERPRISE
                ])
            ],
            'urgency' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high', 'urgent'])
            ],
            'category' => ['sometimes', 'string', 'max:100'],
            'subcategory' => ['sometimes', 'nullable', 'string', 'max:100'],
            'assigned_to' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'assigned_group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'resolution_notes' => ['sometimes', 'nullable', 'string'],
            'customer_notes' => ['sometimes', 'nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'custom_fields' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.string' => 'The incident title must be a valid string.',
            'title.max' => 'The incident title must not exceed 255 characters.',
            'description.string' => 'The incident description must be a valid string.',
            'status.in' => 'The selected status is invalid. Valid options are: open, in_progress, resolved, closed.',
            'priority.in' => 'The selected priority level is invalid.',
            'impact.in' => 'The selected impact level is invalid.',
            'urgency.in' => 'The selected urgency level is invalid.',
            'category.string' => 'The category must be a valid string.',
            'category.max' => 'The category name must not exceed 100 characters.',
            'subcategory.string' => 'The subcategory must be a valid string.',
            'subcategory.max' => 'The subcategory name must not exceed 100 characters.',
            'assigned_to.exists' => 'The selected assignee does not exist.',
            'assigned_group.string' => 'The assigned group must be a valid string.',
            'assigned_group.max' => 'The group name must not exceed 100 characters.',
            'resolution_notes.string' => 'The resolution notes must be a valid string.',
            'customer_notes.string' => 'The customer notes must be a valid string.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag must not exceed 50 characters.',
            'custom_fields.array' => 'Custom fields must be provided as an array.',
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
            'title' => 'incident title',
            'description' => 'incident description',
            'status' => 'incident status',
            'priority' => 'priority level',
            'impact' => 'impact level',
            'urgency' => 'urgency level',
            'category' => 'incident category',
            'subcategory' => 'incident subcategory',
            'assigned_to' => 'assignee',
            'assigned_group' => 'assigned group',
            'resolution_notes' => 'resolution notes',
            'customer_notes' => 'customer notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any fields that are not present in the request
        // This helps with partial updates
        $this->merge(
            collect($this->all())->filter(function ($value) {
                return $value !== null;
            })->toArray()
        );
    }
}