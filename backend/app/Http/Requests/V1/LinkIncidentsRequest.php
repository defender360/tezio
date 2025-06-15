<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkIncidentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('link-incidents-to-problems');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $problem = $this->route('problem');
        
        return [
            'incidents' => ['required', 'array', 'min:1'],
            'incidents.*' => ['required', 'array'],
            'incidents.*.id' => ['required', 'integer', 'exists:incidents,id'],
            'incidents.*.relationship_type' => ['required', Rule::in(['symptom', 'caused_by', 'related', 'duplicate'])],
            'incidents.*.confidence_level' => ['required', Rule::in(['low', 'medium', 'high', 'confirmed'])],
            'incidents.*.notes' => ['nullable', 'string', 'max:1000'],
            'link_reason' => ['required', 'string', 'min:20', 'max:1000'],
            'analysis_method' => ['required', Rule::in(['pattern_matching', 'root_cause', 'timeline_correlation', 'symptom_analysis', 'manual_review'])],
            'common_factors' => ['nullable', 'array'],
            'common_factors.*' => ['required', 'array'],
            'common_factors.*.type' => ['required', Rule::in(['configuration_item', 'service', 'location', 'time_pattern', 'user_group', 'error_code', 'other'])],
            'common_factors.*.value' => ['required', 'string', 'max:255'],
            'common_factors.*.relevance' => ['required', Rule::in(['low', 'medium', 'high'])],
            'impact_assessment' => ['required', 'array'],
            'impact_assessment.total_users_affected' => ['required', 'integer', 'min:0'],
            'impact_assessment.total_downtime_minutes' => ['nullable', 'integer', 'min:0'],
            'impact_assessment.business_services_affected' => ['required', 'array'],
            'impact_assessment.business_services_affected.*' => ['string', 'max:255'],
            'impact_assessment.financial_impact' => ['nullable', 'numeric', 'min:0'],
            'impact_assessment.reputation_impact' => ['nullable', Rule::in(['none', 'minimal', 'moderate', 'significant', 'severe'])],
            'pattern_details' => ['nullable', 'array'],
            'pattern_details.frequency' => ['nullable', Rule::in(['once', 'sporadic', 'regular', 'frequent', 'continuous'])],
            'pattern_details.time_pattern' => ['nullable', Rule::in(['random', 'business_hours', 'after_hours', 'weekends', 'month_end', 'specific_time'])],
            'pattern_details.trigger_conditions' => ['nullable', 'array'],
            'pattern_details.trigger_conditions.*' => ['string', 'max:500'],
            'update_incident_status' => ['required', 'boolean'],
            'incident_updates' => ['required_if:update_incident_status,true', 'nullable', 'array'],
            'incident_updates.add_problem_reference' => ['nullable', 'boolean'],
            'incident_updates.update_resolution_notes' => ['nullable', 'boolean'],
            'incident_updates.notify_incident_owners' => ['nullable', 'boolean'],
            'validation_performed' => ['required', 'boolean'],
            'validation_details' => ['required_if:validation_performed,true', 'nullable', 'array'],
            'validation_details.method' => ['required', Rule::in(['automated', 'manual', 'hybrid'])],
            'validation_details.checks_performed' => ['required', 'array'],
            'validation_details.checks_performed.*' => ['string', 'max:255'],
            'validation_details.anomalies_found' => ['nullable', 'array'],
            'validation_details.anomalies_found.*' => ['string', 'max:500'],
            'auto_link_similar' => ['nullable', 'boolean'],
            'similarity_threshold' => ['required_if:auto_link_similar,true', 'nullable', 'integer', 'min:50', 'max:100'],
            'exclude_incidents' => ['nullable', 'array'],
            'exclude_incidents.*' => ['integer', 'exists:incidents,id'],
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
            'incidents.required' => 'At least one incident must be linked.',
            'incidents.min' => 'At least one incident must be linked.',
            'incidents.*.id.required' => 'Incident ID is required.',
            'incidents.*.id.exists' => 'One or more incidents do not exist.',
            'incidents.*.relationship_type.required' => 'Relationship type must be specified for each incident.',
            'incidents.*.relationship_type.in' => 'Invalid relationship type.',
            'incidents.*.confidence_level.required' => 'Confidence level is required for each link.',
            'incidents.*.confidence_level.in' => 'Invalid confidence level.',
            'link_reason.required' => 'Reason for linking incidents is required.',
            'link_reason.min' => 'Link reason must be at least 20 characters.',
            'analysis_method.required' => 'Analysis method used must be specified.',
            'analysis_method.in' => 'Invalid analysis method.',
            'common_factors.*.type.required' => 'Common factor type is required.',
            'common_factors.*.type.in' => 'Invalid common factor type.',
            'common_factors.*.value.required' => 'Common factor value is required.',
            'common_factors.*.relevance.required' => 'Common factor relevance must be specified.',
            'common_factors.*.relevance.in' => 'Invalid relevance level.',
            'impact_assessment.total_users_affected.required' => 'Total affected users must be specified.',
            'impact_assessment.total_users_affected.min' => 'Affected users cannot be negative.',
            'impact_assessment.total_downtime_minutes.min' => 'Downtime cannot be negative.',
            'impact_assessment.business_services_affected.required' => 'Affected business services must be specified.',
            'impact_assessment.financial_impact.min' => 'Financial impact cannot be negative.',
            'impact_assessment.reputation_impact.in' => 'Invalid reputation impact level.',
            'pattern_details.frequency.in' => 'Invalid frequency pattern.',
            'pattern_details.time_pattern.in' => 'Invalid time pattern.',
            'update_incident_status.required' => 'Please specify if incident status should be updated.',
            'incident_updates.required_if' => 'Incident update details are required when updating status.',
            'validation_performed.required' => 'Please specify if validation was performed.',
            'validation_details.required_if' => 'Validation details are required when validation was performed.',
            'validation_details.method.required' => 'Validation method must be specified.',
            'validation_details.method.in' => 'Invalid validation method.',
            'validation_details.checks_performed.required' => 'Validation checks performed must be listed.',
            'similarity_threshold.required_if' => 'Similarity threshold is required when auto-linking is enabled.',
            'similarity_threshold.min' => 'Similarity threshold must be at least 50%.',
            'similarity_threshold.max' => 'Similarity threshold cannot exceed 100%.',
            'exclude_incidents.*.exists' => 'One or more excluded incidents do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('update_incident_status')) {
            $this->merge([
                'update_incident_status' => filter_var($this->update_incident_status, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('validation_performed')) {
            $this->merge([
                'validation_performed' => filter_var($this->validation_performed, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('auto_link_similar')) {
            $this->merge([
                'auto_link_similar' => filter_var($this->auto_link_similar, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('incident_updates')) {
            $updates = $this->incident_updates;
            foreach (['add_problem_reference', 'update_resolution_notes', 'notify_incident_owners'] as $field) {
                if (isset($updates[$field])) {
                    $updates[$field] = filter_var($updates[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['incident_updates' => $updates]);
        }
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
            if ($this->has('incidents') && $this->has('exclude_incidents')) {
                $incidentIds = collect($this->incidents)->pluck('id')->toArray();
                $excludeIds = $this->exclude_incidents ?? [];
                
                $overlap = array_intersect($incidentIds, $excludeIds);
                if (!empty($overlap)) {
                    $validator->errors()->add('incidents', 'Cannot link and exclude the same incidents.');
                }
            }
        });
    }
}