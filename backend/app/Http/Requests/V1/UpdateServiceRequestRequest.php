<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');
        return $this->user()->can('update-service-requests') || 
               $this->user()->id === $serviceRequest->requested_by;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $serviceRequest = $this->route('serviceRequest');
        $editableStatuses = ['new', 'open', 'pending'];
        
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10', 'max:5000'],
            'category' => ['sometimes', Rule::in(['hardware', 'software', 'network', 'access', 'other'])],
            'subcategory' => ['sometimes', 'string', 'max:100'],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'department' => ['sometimes', 'string', 'max:100'],
            'location' => ['sometimes', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'string', 'max:20'],
            'alternate_contact' => ['nullable', 'string', 'max:20'],
            'urgency' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'business_justification' => ['sometimes', 'string', 'min:20', 'max:1000'],
            'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'additional_information' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'exists:attachments,id'],
        ];

        // Only allow status updates if user has permission
        if ($this->user()->can('manage-service-requests')) {
            $rules['status'] = ['sometimes', Rule::in(['new', 'open', 'pending', 'in_progress', 'fulfilled', 'cancelled', 'closed'])];
            $rules['assigned_to'] = ['nullable', 'integer', 'exists:users,id'];
            $rules['resolution_notes'] = ['required_if:status,fulfilled,closed', 'nullable', 'string', 'min:20', 'max:2000'];
            $rules['cancellation_reason'] = ['required_if:status,cancelled', 'nullable', 'string', 'min:10', 'max:500'];
        }

        // Restrict certain fields based on status
        if (!in_array($serviceRequest->status, $editableStatuses)) {
            // Remove fields that shouldn't be editable after certain statuses
            $restrictedFields = ['title', 'description', 'category', 'subcategory', 'service_type', 'business_justification'];
            foreach ($restrictedFields as $field) {
                unset($rules[$field]);
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.min' => 'Description must be at least 10 characters long.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'category.in' => 'Invalid service category selected.',
            'priority.in' => 'Invalid priority level selected.',
            'urgency.in' => 'Invalid urgency level selected.',
            'business_justification.min' => 'Business justification must be at least 20 characters.',
            'expected_delivery_date.after' => 'Expected delivery date must be in the future.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed types: PDF, Word, Excel, Images, Text.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'remove_attachments.*.exists' => 'One or more attachments to remove were not found.',
            'status.in' => 'Invalid status value.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'resolution_notes.required_if' => 'Resolution notes are required when marking request as fulfilled or closed.',
            'resolution_notes.min' => 'Resolution notes must be at least 20 characters.',
            'cancellation_reason.required_if' => 'Cancellation reason is required when cancelling the request.',
            'cancellation_reason.min' => 'Cancellation reason must be at least 10 characters.',
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
            'expected_delivery_date' => 'expected delivery date',
            'alternate_contact' => 'alternate contact number',
            'remove_attachments' => 'attachments to remove',
        ];
    }
}