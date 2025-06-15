<?php

namespace App\Http\Requests;

use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization - user must be authenticated
        // Specific incident authorization will be checked for each incident
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'incident_ids' => ['required', 'array', 'min:1', 'max:100'],
            'incident_ids.*' => ['required', 'string', 'exists:incidents,id'],
            
            // Fields that can be bulk updated
            'updates' => ['required', 'array'],
            'updates.status' => [
                'sometimes',
                'string',
                Rule::in([
                    Incident::STATUS_OPEN,
                    Incident::STATUS_IN_PROGRESS,
                    Incident::STATUS_RESOLVED,
                    Incident::STATUS_CLOSED
                ])
            ],
            'updates.priority' => [
                'sometimes',
                'string',
                Rule::in([
                    Incident::PRIORITY_LOW,
                    Incident::PRIORITY_MEDIUM,
                    Incident::PRIORITY_HIGH,
                    Incident::PRIORITY_CRITICAL
                ])
            ],
            'updates.assigned_to' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'updates.assigned_group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'updates.category' => ['sometimes', 'string', 'max:100'],
            'updates.tags' => ['sometimes', 'array'],
            'updates.tags.*' => ['string', 'max:50'],
            
            // Optional bulk comment
            'comment' => ['sometimes', 'string', 'max:1000'],
            'notify_assignees' => ['sometimes', 'boolean'],
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
            'incident_ids.required' => 'Please select at least one incident to update.',
            'incident_ids.array' => 'Incident IDs must be provided as an array.',
            'incident_ids.min' => 'Please select at least one incident to update.',
            'incident_ids.max' => 'You can update a maximum of 100 incidents at once.',
            'incident_ids.*.required' => 'Each incident ID is required.',
            'incident_ids.*.string' => 'Each incident ID must be a string.',
            'incident_ids.*.exists' => 'One or more selected incidents do not exist.',
            
            'updates.required' => 'Please provide the fields to update.',
            'updates.array' => 'Updates must be provided as an array.',
            'updates.status.in' => 'The selected status is invalid.',
            'updates.priority.in' => 'The selected priority level is invalid.',
            'updates.assigned_to.exists' => 'The selected assignee does not exist.',
            'updates.assigned_group.max' => 'The group name must not exceed 100 characters.',
            'updates.category.max' => 'The category name must not exceed 100 characters.',
            'updates.tags.array' => 'Tags must be provided as an array.',
            'updates.tags.*.string' => 'Each tag must be a string.',
            'updates.tags.*.max' => 'Each tag must not exceed 50 characters.',
            
            'comment.string' => 'The comment must be a valid string.',
            'comment.max' => 'The comment must not exceed 1000 characters.',
            'notify_assignees.boolean' => 'The notify assignees flag must be true or false.',
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
            'incident_ids' => 'selected incidents',
            'incident_ids.*' => 'incident',
            'updates' => 'update fields',
            'updates.status' => 'status',
            'updates.priority' => 'priority',
            'updates.assigned_to' => 'assignee',
            'updates.assigned_group' => 'assigned group',
            'updates.category' => 'category',
            'updates.tags' => 'tags',
            'comment' => 'bulk comment',
            'notify_assignees' => 'notify assignees option',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->hasAnyUpdates()) {
                $validator->errors()->add('updates', 'Please provide at least one field to update.');
            }

            // Validate user can update all selected incidents
            if ($this->has('incident_ids') && is_array($this->incident_ids)) {
                $unauthorizedIncidents = collect($this->incident_ids)
                    ->filter(function ($incidentId) {
                        $incident = Incident::find($incidentId);
                        return !$incident || !$this->user()->can('update', $incident);
                    });

                if ($unauthorizedIncidents->isNotEmpty()) {
                    $validator->errors()->add(
                        'incident_ids',
                        'You are not authorized to update one or more selected incidents.'
                    );
                }
            }

            // Validate bulk status changes
            if ($this->input('updates.status') === Incident::STATUS_RESOLVED) {
                // Check if resolution notes are required
                $needsResolutionNotes = collect($this->incident_ids ?? [])
                    ->some(function ($incidentId) {
                        $incident = Incident::find($incidentId);
                        return $incident && !$incident->resolution_notes && $incident->status !== Incident::STATUS_RESOLVED;
                    });

                if ($needsResolutionNotes && !$this->has('comment')) {
                    $validator->errors()->add(
                        'comment',
                        'A comment is required when resolving incidents without resolution notes.'
                    );
                }
            }
        });
    }

    /**
     * Check if the request has any valid update fields.
     *
     * @return bool
     */
    protected function hasAnyUpdates(): bool
    {
        $updates = $this->input('updates', []);
        $validUpdateFields = ['status', 'priority', 'assigned_to', 'assigned_group', 'category', 'tags'];
        
        return collect($validUpdateFields)
            ->some(fn($field) => array_key_exists($field, $updates));
    }
}