<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiskAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('perform-risk-assessment');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'change_id' => ['required', 'integer', 'exists:changes,id'],
            'assessment_type' => ['required', Rule::in(['initial', 'detailed', 'final', 'post_implementation'])],
            'risk_categories' => ['required', 'array', 'min:1'],
            'risk_categories.*' => ['required', 'array'],
            'risk_categories.*.category' => ['required', Rule::in(['technical', 'business', 'security', 'compliance', 'operational', 'financial'])],
            'risk_categories.*.probability' => ['required', Rule::in(['very_low', 'low', 'medium', 'high', 'very_high'])],
            'risk_categories.*.impact' => ['required', Rule::in(['negligible', 'minor', 'moderate', 'major', 'severe'])],
            'risk_categories.*.description' => ['required', 'string', 'min:20', 'max:500'],
            'risk_categories.*.mitigation_strategy' => ['required', 'string', 'min:20', 'max:1000'],
            'overall_risk_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'risk_owner' => ['required', 'integer', 'exists:users,id'],
            'identified_risks' => ['required', 'array', 'min:1'],
            'identified_risks.*' => ['required', 'array'],
            'identified_risks.*.title' => ['required', 'string', 'max:255'],
            'identified_risks.*.description' => ['required', 'string', 'min:20', 'max:1000'],
            'identified_risks.*.likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'identified_risks.*.impact' => ['required', 'integer', 'min:1', 'max:5'],
            'identified_risks.*.detection_difficulty' => ['required', 'integer', 'min:1', 'max:5'],
            'identified_risks.*.mitigation_actions' => ['required', 'array', 'min:1'],
            'identified_risks.*.mitigation_actions.*' => ['string', 'min:10', 'max:500'],
            'identified_risks.*.contingency_plan' => ['nullable', 'string', 'min:20', 'max:1000'],
            'identified_risks.*.trigger_indicators' => ['nullable', 'array'],
            'identified_risks.*.trigger_indicators.*' => ['string', 'max:255'],
            'recommendations' => ['required', 'string', 'min:50', 'max:2000'],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string', 'max:500'],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*' => ['string', 'max:500'],
            'assessment_validity_period' => ['required', 'integer', 'min:1', 'max:365'],
            'review_required' => ['required', 'boolean'],
            'reviewers' => ['required_if:review_required,true', 'nullable', 'array', 'min:1'],
            'reviewers.*' => ['integer', 'exists:users,id'],
            'supporting_documents' => ['nullable', 'array', 'max:5'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
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
            'change_id.required' => 'Change ID is required for risk assessment.',
            'change_id.exists' => 'The specified change does not exist.',
            'assessment_type.required' => 'Assessment type must be specified.',
            'assessment_type.in' => 'Invalid assessment type selected.',
            'risk_categories.required' => 'At least one risk category must be assessed.',
            'risk_categories.min' => 'At least one risk category must be assessed.',
            'risk_categories.*.category.required' => 'Risk category type is required.',
            'risk_categories.*.category.in' => 'Invalid risk category type.',
            'risk_categories.*.probability.required' => 'Risk probability assessment is required.',
            'risk_categories.*.probability.in' => 'Invalid risk probability level.',
            'risk_categories.*.impact.required' => 'Risk impact assessment is required.',
            'risk_categories.*.impact.in' => 'Invalid risk impact level.',
            'risk_categories.*.description.required' => 'Risk description is required.',
            'risk_categories.*.description.min' => 'Risk description must be at least 20 characters.',
            'risk_categories.*.mitigation_strategy.required' => 'Mitigation strategy is required for each risk.',
            'risk_categories.*.mitigation_strategy.min' => 'Mitigation strategy must be at least 20 characters.',
            'overall_risk_score.required' => 'Overall risk score is required.',
            'overall_risk_score.min' => 'Risk score cannot be negative.',
            'overall_risk_score.max' => 'Risk score cannot exceed 100.',
            'risk_owner.required' => 'Risk owner must be assigned.',
            'risk_owner.exists' => 'Selected risk owner does not exist.',
            'identified_risks.required' => 'At least one risk must be identified.',
            'identified_risks.min' => 'At least one risk must be identified.',
            'identified_risks.*.title.required' => 'Risk title is required.',
            'identified_risks.*.description.required' => 'Risk description is required.',
            'identified_risks.*.description.min' => 'Risk description must be at least 20 characters.',
            'identified_risks.*.likelihood.required' => 'Risk likelihood rating is required.',
            'identified_risks.*.likelihood.min' => 'Likelihood rating must be between 1 and 5.',
            'identified_risks.*.likelihood.max' => 'Likelihood rating must be between 1 and 5.',
            'identified_risks.*.impact.required' => 'Risk impact rating is required.',
            'identified_risks.*.impact.min' => 'Impact rating must be between 1 and 5.',
            'identified_risks.*.impact.max' => 'Impact rating must be between 1 and 5.',
            'identified_risks.*.detection_difficulty.required' => 'Detection difficulty rating is required.',
            'identified_risks.*.detection_difficulty.min' => 'Detection difficulty must be between 1 and 5.',
            'identified_risks.*.detection_difficulty.max' => 'Detection difficulty must be between 1 and 5.',
            'identified_risks.*.mitigation_actions.required' => 'At least one mitigation action is required.',
            'identified_risks.*.mitigation_actions.min' => 'At least one mitigation action is required.',
            'identified_risks.*.mitigation_actions.*.min' => 'Mitigation action must be at least 10 characters.',
            'recommendations.required' => 'Risk assessment recommendations are required.',
            'recommendations.min' => 'Recommendations must be at least 50 characters.',
            'assessment_validity_period.required' => 'Assessment validity period is required.',
            'assessment_validity_period.min' => 'Validity period must be at least 1 day.',
            'assessment_validity_period.max' => 'Validity period cannot exceed 365 days.',
            'review_required.required' => 'Please specify if review is required.',
            'reviewers.required_if' => 'Reviewers must be specified when review is required.',
            'reviewers.min' => 'At least one reviewer must be specified.',
            'reviewers.*.exists' => 'One or more selected reviewers do not exist.',
            'supporting_documents.max' => 'Maximum 5 supporting documents allowed.',
            'supporting_documents.*.mimes' => 'Only PDF, Word, and Excel files are allowed.',
            'supporting_documents.*.max' => 'Each document must not exceed 10MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('review_required')) {
            $this->merge([
                'review_required' => filter_var($this->review_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}