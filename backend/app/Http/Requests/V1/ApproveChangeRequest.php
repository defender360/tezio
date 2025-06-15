<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $change = $this->route('change');
        return $this->user()->can('approve-changes') && 
               in_array($change->status, ['pending', 'under_review']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected', 'needs_more_info'])],
            'comments' => ['required', 'string', 'min:10', 'max:1000'],
            'conditions' => ['nullable', 'array'],
            'conditions.*' => ['string', 'max:500'],
            'risk_mitigation_required' => ['nullable', 'boolean'],
            'risk_mitigation_plan' => ['required_if:risk_mitigation_required,true', 'nullable', 'string', 'min:50'],
            'approval_level' => ['required', Rule::in(['technical', 'business', 'cab', 'emergency'])],
            'implementation_window' => ['nullable', 'array'],
            'implementation_window.start' => ['required_with:implementation_window', 'date', 'after:now'],
            'implementation_window.end' => ['required_with:implementation_window', 'date', 'after:implementation_window.start'],
            'required_approvers' => ['nullable', 'array'],
            'required_approvers.*' => ['integer', 'exists:users,id'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx', 'max:5120'],
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
            'decision.required' => 'An approval decision is required.',
            'decision.in' => 'Invalid approval decision.',
            'comments.required' => 'Approval comments are required.',
            'comments.min' => 'Comments must be at least 10 characters long.',
            'comments.max' => 'Comments cannot exceed 1000 characters.',
            'conditions.*.max' => 'Each condition cannot exceed 500 characters.',
            'risk_mitigation_plan.required_if' => 'Risk mitigation plan is required when risk mitigation is needed.',
            'risk_mitigation_plan.min' => 'Risk mitigation plan must be at least 50 characters long.',
            'approval_level.required' => 'Approval level must be specified.',
            'approval_level.in' => 'Invalid approval level.',
            'implementation_window.start.required_with' => 'Implementation window start time is required.',
            'implementation_window.start.after' => 'Implementation window must be in the future.',
            'implementation_window.end.required_with' => 'Implementation window end time is required.',
            'implementation_window.end.after' => 'Implementation window end must be after start time.',
            'required_approvers.*.exists' => 'One or more specified approvers do not exist.',
            'attachments.max' => 'Maximum 3 supporting documents allowed.',
            'attachments.*.mimes' => 'Only PDF and Word documents are allowed as attachments.',
            'attachments.*.max' => 'Each attachment must not exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('risk_mitigation_required')) {
            $this->merge([
                'risk_mitigation_required' => filter_var($this->risk_mitigation_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}