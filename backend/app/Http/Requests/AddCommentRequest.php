<?php

namespace App\Http\Requests;

use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');
        
        // Check if user can comment on this incident
        // Users can comment if they can view the incident
        return $incident && $this->user() && $this->user()->can('view', $incident);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'mentioned_users' => ['sometimes', 'array'],
            'mentioned_users.*' => ['string', 'exists:users,id'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip'],
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
            'comment.required' => 'Please enter a comment.',
            'comment.string' => 'The comment must be a valid text.',
            'comment.min' => 'The comment cannot be empty.',
            'comment.max' => 'The comment must not exceed 5000 characters.',
            'is_internal.boolean' => 'The internal flag must be true or false.',
            'mentioned_users.array' => 'Mentioned users must be provided as an array.',
            'mentioned_users.*.string' => 'Each mentioned user ID must be a string.',
            'mentioned_users.*.exists' => 'One or more mentioned users do not exist.',
            'attachments.array' => 'Attachments must be provided as an array.',
            'attachments.max' => 'You can upload a maximum of 5 attachments.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'attachments.*.mimes' => 'Invalid file type. Allowed types: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, zip.',
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
            'comment' => 'comment text',
            'is_internal' => 'internal visibility',
            'mentioned_users' => 'mentioned users',
            'mentioned_users.*' => 'mentioned user',
            'attachments' => 'file attachments',
            'attachments.*' => 'attachment file',
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
            // Additional validation for internal comments
            if ($this->boolean('is_internal') && !$this->user()->can('create-internal-comments')) {
                $validator->errors()->add('is_internal', 'You are not authorized to create internal comments.');
            }

            // Validate mentioned users belong to the same tenant
            if ($this->has('mentioned_users') && is_array($this->mentioned_users)) {
                $tenantId = $this->user()->tenant_id;
                $invalidUsers = collect($this->mentioned_users)
                    ->filter(function ($userId) use ($tenantId) {
                        $user = \App\Models\User::find($userId);
                        return !$user || $user->tenant_id !== $tenantId;
                    });

                if ($invalidUsers->isNotEmpty()) {
                    $validator->errors()->add('mentioned_users', 'Some mentioned users are not from your organization.');
                }
            }
        });
    }
}