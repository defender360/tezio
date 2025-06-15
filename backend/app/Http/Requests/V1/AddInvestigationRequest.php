<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddInvestigationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $problem = $this->route('problem');
        return $this->user()->can('investigate-problems') || 
               in_array($this->user()->id, $problem->investigation_team ?? []);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'investigation_type' => ['required', Rule::in(['initial', 'detailed', 'root_cause', 'impact', 'technical', 'business'])],
            'investigation_phase' => ['required', Rule::in(['data_collection', 'analysis', 'testing', 'validation', 'conclusion'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'methodology' => ['required', 'string', 'min:20', 'max:1000'],
            'findings' => ['required', 'array', 'min:1'],
            'findings.*' => ['required', 'array'],
            'findings.*.category' => ['required', Rule::in(['technical', 'process', 'people', 'environmental', 'other'])],
            'findings.*.description' => ['required', 'string', 'min:20', 'max:1000'],
            'findings.*.evidence' => ['nullable', 'string', 'max:2000'],
            'findings.*.severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'findings.*.contributing_factor' => ['required', 'boolean'],
            'data_sources' => ['required', 'array', 'min:1'],
            'data_sources.*' => ['required', 'array'],
            'data_sources.*.type' => ['required', Rule::in(['logs', 'monitoring', 'interviews', 'documentation', 'testing', 'other'])],
            'data_sources.*.description' => ['required', 'string', 'max:500'],
            'data_sources.*.reliability' => ['required', Rule::in(['high', 'medium', 'low'])],
            'hypotheses' => ['nullable', 'array'],
            'hypotheses.*' => ['required', 'array'],
            'hypotheses.*.statement' => ['required', 'string', 'min:20', 'max:500'],
            'hypotheses.*.tested' => ['required', 'boolean'],
            'hypotheses.*.result' => ['required_if:hypotheses.*.tested,true', 'nullable', Rule::in(['confirmed', 'rejected', 'inconclusive'])],
            'hypotheses.*.notes' => ['nullable', 'string', 'max:1000'],
            'timeline_events' => ['nullable', 'array'],
            'timeline_events.*' => ['required', 'array'],
            'timeline_events.*.timestamp' => ['required', 'date'],
            'timeline_events.*.event' => ['required', 'string', 'max:500'],
            'timeline_events.*.significance' => ['required', Rule::in(['low', 'medium', 'high'])],
            'recommendations' => ['required', 'array', 'min:1'],
            'recommendations.*' => ['required', 'array'],
            'recommendations.*.type' => ['required', Rule::in(['immediate', 'short_term', 'long_term'])],
            'recommendations.*.action' => ['required', 'string', 'min:20', 'max:1000'],
            'recommendations.*.priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'recommendations.*.owner' => ['nullable', 'integer', 'exists:users,id'],
            'recommendations.*.target_date' => ['nullable', 'date', 'after:today'],
            'tools_used' => ['nullable', 'array'],
            'tools_used.*' => ['string', 'max:100'],
            'investigation_status' => ['required', Rule::in(['ongoing', 'completed', 'blocked', 'requires_escalation'])],
            'next_steps' => ['required_if:investigation_status,ongoing,blocked', 'nullable', 'string', 'min:20', 'max:1000'],
            'blockers' => ['required_if:investigation_status,blocked', 'nullable', 'array'],
            'blockers.*' => ['string', 'min:10', 'max:500'],
            'escalation_required' => ['required_if:investigation_status,requires_escalation', 'nullable', 'boolean'],
            'escalation_reason' => ['required_if:escalation_required,true', 'nullable', 'string', 'min:20', 'max:500'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'exists:users,id'],
            'time_spent' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,log,txt,csv', 'max:20480'],
            'confidence_level' => ['required', Rule::in(['low', 'medium', 'high', 'very_high'])],
            'requires_further_investigation' => ['required', 'boolean'],
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
            'investigation_type.required' => 'Investigation type must be specified.',
            'investigation_type.in' => 'Invalid investigation type selected.',
            'investigation_phase.required' => 'Investigation phase must be specified.',
            'investigation_phase.in' => 'Invalid investigation phase selected.',
            'title.required' => 'Investigation title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Investigation description is required.',
            'description.min' => 'Description must be at least 50 characters.',
            'methodology.required' => 'Investigation methodology must be described.',
            'methodology.min' => 'Methodology description must be at least 20 characters.',
            'findings.required' => 'At least one finding must be documented.',
            'findings.min' => 'At least one finding must be documented.',
            'findings.*.category.required' => 'Finding category is required.',
            'findings.*.category.in' => 'Invalid finding category.',
            'findings.*.description.required' => 'Finding description is required.',
            'findings.*.description.min' => 'Finding description must be at least 20 characters.',
            'findings.*.severity.required' => 'Finding severity must be specified.',
            'findings.*.severity.in' => 'Invalid severity level.',
            'findings.*.contributing_factor.required' => 'Please specify if this is a contributing factor.',
            'data_sources.required' => 'At least one data source must be specified.',
            'data_sources.min' => 'At least one data source must be specified.',
            'data_sources.*.type.required' => 'Data source type is required.',
            'data_sources.*.type.in' => 'Invalid data source type.',
            'data_sources.*.description.required' => 'Data source description is required.',
            'data_sources.*.reliability.required' => 'Data source reliability must be assessed.',
            'data_sources.*.reliability.in' => 'Invalid reliability level.',
            'hypotheses.*.statement.required' => 'Hypothesis statement is required.',
            'hypotheses.*.statement.min' => 'Hypothesis must be at least 20 characters.',
            'hypotheses.*.tested.required' => 'Please specify if hypothesis was tested.',
            'hypotheses.*.result.required_if' => 'Test result is required for tested hypotheses.',
            'hypotheses.*.result.in' => 'Invalid test result.',
            'timeline_events.*.timestamp.required' => 'Event timestamp is required.',
            'timeline_events.*.event.required' => 'Event description is required.',
            'timeline_events.*.significance.required' => 'Event significance must be specified.',
            'timeline_events.*.significance.in' => 'Invalid significance level.',
            'recommendations.required' => 'At least one recommendation must be provided.',
            'recommendations.min' => 'At least one recommendation must be provided.',
            'recommendations.*.type.required' => 'Recommendation type is required.',
            'recommendations.*.type.in' => 'Invalid recommendation type.',
            'recommendations.*.action.required' => 'Recommendation action is required.',
            'recommendations.*.action.min' => 'Recommendation must be at least 20 characters.',
            'recommendations.*.priority.required' => 'Recommendation priority is required.',
            'recommendations.*.priority.in' => 'Invalid priority level.',
            'recommendations.*.owner.exists' => 'Selected owner does not exist.',
            'recommendations.*.target_date.after' => 'Target date must be in the future.',
            'investigation_status.required' => 'Investigation status is required.',
            'investigation_status.in' => 'Invalid investigation status.',
            'next_steps.required_if' => 'Next steps are required for ongoing or blocked investigations.',
            'next_steps.min' => 'Next steps must be at least 20 characters.',
            'blockers.required_if' => 'Blockers must be specified when investigation is blocked.',
            'blockers.*.min' => 'Each blocker description must be at least 10 characters.',
            'escalation_reason.required_if' => 'Escalation reason is required.',
            'escalation_reason.min' => 'Escalation reason must be at least 20 characters.',
            'participants.required' => 'Investigation participants must be specified.',
            'participants.min' => 'At least one participant must be specified.',
            'participants.*.exists' => 'One or more participants do not exist.',
            'time_spent.required' => 'Time spent on investigation is required.',
            'time_spent.min' => 'Minimum time is 0.25 hours (15 minutes).',
            'time_spent.max' => 'Maximum time cannot exceed 999.99 hours.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Excel, Images, Log files, Text, CSV.',
            'attachments.*.max' => 'Each attachment must not exceed 20MB.',
            'confidence_level.required' => 'Confidence level in findings is required.',
            'confidence_level.in' => 'Invalid confidence level.',
            'requires_further_investigation.required' => 'Please specify if further investigation is needed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('findings')) {
            $findings = $this->findings;
            foreach ($findings as $key => $finding) {
                if (isset($finding['contributing_factor'])) {
                    $findings[$key]['contributing_factor'] = filter_var($finding['contributing_factor'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['findings' => $findings]);
        }

        if ($this->has('hypotheses')) {
            $hypotheses = $this->hypotheses;
            foreach ($hypotheses as $key => $hypothesis) {
                if (isset($hypothesis['tested'])) {
                    $hypotheses[$key]['tested'] = filter_var($hypothesis['tested'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['hypotheses' => $hypotheses]);
        }

        if ($this->has('escalation_required')) {
            $this->merge([
                'escalation_required' => filter_var($this->escalation_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('requires_further_investigation')) {
            $this->merge([
                'requires_further_investigation' => filter_var($this->requires_further_investigation, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}