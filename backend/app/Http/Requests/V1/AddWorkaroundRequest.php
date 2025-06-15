<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddWorkaroundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-problem-workarounds');
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
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'type' => ['required', Rule::in(['temporary', 'permanent', 'preventive'])],
            'effectiveness' => ['required', Rule::in(['low', 'medium', 'high', 'complete'])],
            'implementation_steps' => ['required', 'array', 'min:1'],
            'implementation_steps.*' => ['required', 'array'],
            'implementation_steps.*.order' => ['required', 'integer', 'min:1'],
            'implementation_steps.*.action' => ['required', 'string', 'min:10', 'max:1000'],
            'implementation_steps.*.responsible_role' => ['required', 'string', 'max:100'],
            'implementation_steps.*.estimated_time' => ['nullable', 'integer', 'min:1', 'max:999'],
            'implementation_steps.*.requires_approval' => ['required', 'boolean'],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string', 'min:10', 'max:500'],
            'risks' => ['nullable', 'array'],
            'risks.*' => ['required', 'array'],
            'risks.*.description' => ['required', 'string', 'min:10', 'max:500'],
            'risks.*.likelihood' => ['required', Rule::in(['low', 'medium', 'high'])],
            'risks.*.impact' => ['required', Rule::in(['low', 'medium', 'high'])],
            'risks.*.mitigation' => ['nullable', 'string', 'max:500'],
            'success_criteria' => ['required', 'array', 'min:1'],
            'success_criteria.*' => ['string', 'min:10', 'max:500'],
            'affected_services' => ['required', 'array', 'min:1'],
            'affected_services.*' => ['string', 'max:255'],
            'affected_cis' => ['nullable', 'array'],
            'affected_cis.*' => ['integer', 'exists:configuration_items,id'],
            'implementation_time' => ['required', 'integer', 'min:1', 'max:999'],
            'rollback_procedure' => ['nullable', 'string', 'min:20', 'max:2000'],
            'monitoring_requirements' => ['nullable', 'array'],
            'monitoring_requirements.*' => ['string', 'max:500'],
            'automation_possible' => ['required', 'boolean'],
            'automation_details' => ['required_if:automation_possible,true', 'nullable', 'array'],
            'automation_details.script_location' => ['nullable', 'string', 'max:500'],
            'automation_details.tool_required' => ['nullable', 'string', 'max:255'],
            'automation_details.estimated_effort' => ['nullable', 'integer', 'min:1', 'max:999'],
            'tested' => ['required', 'boolean'],
            'test_results' => ['required_if:tested,true', 'nullable', 'array'],
            'test_results.environment' => ['required', 'string', 'max:100'],
            'test_results.date' => ['required', 'date', 'before_or_equal:today'],
            'test_results.tester' => ['required', 'integer', 'exists:users,id'],
            'test_results.outcome' => ['required', Rule::in(['successful', 'partially_successful', 'failed'])],
            'test_results.notes' => ['nullable', 'string', 'max:1000'],
            'approval_required' => ['required', 'boolean'],
            'approvers' => ['required_if:approval_required,true', 'nullable', 'array', 'min:1'],
            'approvers.*' => ['integer', 'exists:users,id'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'usage_count' => ['nullable', 'integer', 'min:0'],
            'success_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'documentation_links' => ['nullable', 'array', 'max:5'],
            'documentation_links.*' => ['url', 'max:500'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,sh,ps1,py', 'max:10240'],
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
            'title.required' => 'Workaround title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Workaround description is required.',
            'description.min' => 'Description must be at least 50 characters.',
            'type.required' => 'Workaround type must be specified.',
            'type.in' => 'Invalid workaround type.',
            'effectiveness.required' => 'Effectiveness level must be specified.',
            'effectiveness.in' => 'Invalid effectiveness level.',
            'implementation_steps.required' => 'At least one implementation step is required.',
            'implementation_steps.min' => 'At least one implementation step is required.',
            'implementation_steps.*.order.required' => 'Step order is required.',
            'implementation_steps.*.order.min' => 'Step order must be positive.',
            'implementation_steps.*.action.required' => 'Step action is required.',
            'implementation_steps.*.action.min' => 'Step action must be at least 10 characters.',
            'implementation_steps.*.responsible_role.required' => 'Responsible role for each step is required.',
            'implementation_steps.*.estimated_time.min' => 'Estimated time must be at least 1 minute.',
            'implementation_steps.*.requires_approval.required' => 'Please specify if step requires approval.',
            'prerequisites.*.min' => 'Each prerequisite must be at least 10 characters.',
            'risks.*.description.required' => 'Risk description is required.',
            'risks.*.description.min' => 'Risk description must be at least 10 characters.',
            'risks.*.likelihood.required' => 'Risk likelihood must be specified.',
            'risks.*.likelihood.in' => 'Invalid risk likelihood.',
            'risks.*.impact.required' => 'Risk impact must be specified.',
            'risks.*.impact.in' => 'Invalid risk impact.',
            'success_criteria.required' => 'At least one success criterion is required.',
            'success_criteria.min' => 'At least one success criterion is required.',
            'success_criteria.*.min' => 'Each success criterion must be at least 10 characters.',
            'affected_services.required' => 'At least one affected service must be specified.',
            'affected_services.min' => 'At least one affected service must be specified.',
            'affected_cis.*.exists' => 'One or more configuration items do not exist.',
            'implementation_time.required' => 'Implementation time estimate is required.',
            'implementation_time.min' => 'Implementation time must be at least 1 minute.',
            'rollback_procedure.min' => 'Rollback procedure must be at least 20 characters.',
            'automation_possible.required' => 'Please specify if automation is possible.',
            'automation_details.required_if' => 'Automation details are required when automation is possible.',
            'tested.required' => 'Please specify if workaround has been tested.',
            'test_results.required_if' => 'Test results are required when workaround has been tested.',
            'test_results.environment.required' => 'Test environment must be specified.',
            'test_results.date.required' => 'Test date is required.',
            'test_results.date.before_or_equal' => 'Test date cannot be in the future.',
            'test_results.tester.required' => 'Tester information is required.',
            'test_results.tester.exists' => 'Selected tester does not exist.',
            'test_results.outcome.required' => 'Test outcome must be specified.',
            'test_results.outcome.in' => 'Invalid test outcome.',
            'approval_required.required' => 'Please specify if approval is required.',
            'approvers.required_if' => 'At least one approver must be specified when approval is required.',
            'approvers.min' => 'At least one approver must be specified.',
            'approvers.*.exists' => 'One or more approvers do not exist.',
            'valid_until.after' => 'Valid until date must be in the future.',
            'usage_count.min' => 'Usage count cannot be negative.',
            'success_rate.min' => 'Success rate cannot be negative.',
            'success_rate.max' => 'Success rate cannot exceed 100%.',
            'documentation_links.max' => 'Maximum 5 documentation links allowed.',
            'documentation_links.*.url' => 'Each documentation link must be a valid URL.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Text, Scripts (sh, ps1, py).',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'notification_list.*.email' => 'Invalid email address in notification list.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('automation_possible')) {
            $this->merge([
                'automation_possible' => filter_var($this->automation_possible, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('tested')) {
            $this->merge([
                'tested' => filter_var($this->tested, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('approval_required')) {
            $this->merge([
                'approval_required' => filter_var($this->approval_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('implementation_steps')) {
            $steps = $this->implementation_steps;
            foreach ($steps as $key => $step) {
                if (isset($step['requires_approval'])) {
                    $steps[$key]['requires_approval'] = filter_var($step['requires_approval'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['implementation_steps' => $steps]);
        }
    }
}