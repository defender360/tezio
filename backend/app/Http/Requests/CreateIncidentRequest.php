<?php

namespace App\Http\Requests;

use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can create incidents
        return $this->user() && $this->user()->can('create', Incident::class);
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
            'description' => ['required', 'string'],
            'priority' => [
                'required',
                'string',
                Rule::in([
                    Incident::PRIORITY_LOW,
                    Incident::PRIORITY_MEDIUM,
                    Incident::PRIORITY_HIGH,
                    Incident::PRIORITY_CRITICAL
                ])
            ],
            'impact' => [
                'required',
                'string',
                Rule::in([
                    Incident::IMPACT_LOW,
                    Incident::IMPACT_MEDIUM,
                    Incident::IMPACT_HIGH,
                    Incident::IMPACT_ENTERPRISE
                ])
            ],
            'urgency' => [
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'urgent'])
            ],
            'category' => ['required', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'string', 'exists:users,id'],
            'assigned_group' => ['nullable', 'string', 'max:100'],
            'customer_notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
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
            'title.required' => 'The incident title is required.',
            'title.max' => 'The incident title must not exceed 255 characters.',
            'description.required' => 'Please provide a detailed description of the incident.',
            'priority.required' => 'Please select a priority level for this incident.',
            'priority.in' => 'The selected priority level is invalid.',
            'impact.required' => 'Please select the impact level of this incident.',
            'impact.in' => 'The selected impact level is invalid.',
            'urgency.required' => 'Please select the urgency level.',
            'urgency.in' => 'The selected urgency level is invalid.',
            'category.required' => 'Please select an incident category.',
            'category.max' => 'The category name must not exceed 100 characters.',
            'subcategory.max' => 'The subcategory name must not exceed 100 characters.',
            'assigned_to.exists' => 'The selected assignee does not exist.',
            'assigned_group.max' => 'The group name must not exceed 100 characters.',
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
            'priority' => 'priority level',
            'impact' => 'impact level',
            'urgency' => 'urgency level',
            'category' => 'incident category',
            'subcategory' => 'incident subcategory',
            'assigned_to' => 'assignee',
            'assigned_group' => 'assigned group',
            'customer_notes' => 'customer notes',
        ];
    }
}