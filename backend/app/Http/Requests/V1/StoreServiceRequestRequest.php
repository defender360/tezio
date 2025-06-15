<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-service-requests');
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
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => ['required', Rule::in(['hardware', 'software', 'network', 'access', 'other'])],
            'subcategory' => ['required', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'requested_for' => ['nullable', 'integer', 'exists:users,id'],
            'department' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'alternate_contact' => ['nullable', 'string', 'max:20'],
            'service_type' => ['required', Rule::in(['new', 'modification', 'removal', 'information'])],
            'urgency' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'business_justification' => ['required', 'string', 'min:20', 'max:1000'],
            'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'budget_approved' => ['required', 'boolean'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'cost_center' => ['required_if:budget_approved,true', 'nullable', 'string', 'max:50'],
            'additional_information' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt', 'max:10240'],
            'related_items' => ['nullable', 'array'],
            'related_items.*.type' => ['required', Rule::in(['incident', 'change', 'problem', 'asset'])],
            'related_items.*.id' => ['required', 'integer'],
            'approval_required' => ['required', 'boolean'],
            'approvers' => ['required_if:approval_required,true', 'nullable', 'array', 'min:1'],
            'approvers.*' => ['integer', 'exists:users,id'],
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
            'title.required' => 'Service request title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'A detailed description is required.',
            'description.min' => 'Description must be at least 10 characters long.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'category.required' => 'Service category must be selected.',
            'category.in' => 'Invalid service category selected.',
            'subcategory.required' => 'Service subcategory is required.',
            'priority.required' => 'Priority level must be specified.',
            'priority.in' => 'Invalid priority level selected.',
            'requested_for.exists' => 'The user requesting this service does not exist.',
            'department.required' => 'Department information is required.',
            'location.required' => 'Location information is required.',
            'contact_number.required' => 'Contact number is required.',
            'service_type.required' => 'Service type must be specified.',
            'service_type.in' => 'Invalid service type selected.',
            'urgency.required' => 'Urgency level must be specified.',
            'urgency.in' => 'Invalid urgency level selected.',
            'business_justification.required' => 'Business justification is required.',
            'business_justification.min' => 'Business justification must be at least 20 characters.',
            'expected_delivery_date.after' => 'Expected delivery date must be in the future.',
            'budget_approved.required' => 'Budget approval status must be specified.',
            'estimated_cost.numeric' => 'Estimated cost must be a valid amount.',
            'estimated_cost.max' => 'Estimated cost cannot exceed 999,999.99.',
            'cost_center.required_if' => 'Cost center is required when budget is approved.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed types: PDF, Word, Excel, Images, Text.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'related_items.*.type.required' => 'Related item type must be specified.',
            'related_items.*.type.in' => 'Invalid related item type.',
            'related_items.*.id.required' => 'Related item ID is required.',
            'approval_required.required' => 'Please specify if approval is required.',
            'approvers.required_if' => 'At least one approver must be specified when approval is required.',
            'approvers.min' => 'At least one approver must be specified.',
            'approvers.*.exists' => 'One or more selected approvers do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('budget_approved')) {
            $this->merge([
                'budget_approved' => filter_var($this->budget_approved, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('approval_required')) {
            $this->merge([
                'approval_required' => filter_var($this->approval_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // If requested_for is not provided, default to the current user
        if (!$this->has('requested_for') || is_null($this->requested_for)) {
            $this->merge([
                'requested_for' => $this->user()->id,
            ]);
        }
    }
}