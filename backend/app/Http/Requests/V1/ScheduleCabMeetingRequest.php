<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleCabMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('schedule-cab-meetings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meeting_type' => ['required', Rule::in(['regular', 'emergency', 'expedited'])],
            'scheduled_date' => ['required', 'date', 'after:now'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:15', 'max:240'],
            'location' => ['required_without:virtual_meeting_link', 'nullable', 'string', 'max:255'],
            'virtual_meeting_link' => ['required_without:location', 'nullable', 'url', 'max:500'],
            'agenda' => ['required', 'string', 'min:50', 'max:2000'],
            'changes' => ['required', 'array', 'min:1'],
            'changes.*' => ['integer', 'exists:changes,id'],
            'attendees' => ['required', 'array', 'min:3'],
            'attendees.*' => ['integer', 'exists:users,id'],
            'optional_attendees' => ['nullable', 'array'],
            'optional_attendees.*' => ['integer', 'exists:users,id'],
            'chairperson' => ['required', 'integer', 'exists:users,id'],
            'secretary' => ['required', 'integer', 'exists:users,id', 'different:chairperson'],
            'pre_read_documents' => ['nullable', 'array', 'max:10'],
            'pre_read_documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:20480'],
            'meeting_notes_template' => ['nullable', 'string', 'max:1000'],
            'voting_required' => ['required', 'boolean'],
            'quorum_percentage' => ['required_if:voting_required,true', 'nullable', 'integer', 'min:50', 'max:100'],
            'reminder_settings' => ['nullable', 'array'],
            'reminder_settings.days_before' => ['required_with:reminder_settings', 'integer', 'min:1', 'max:7'],
            'reminder_settings.send_agenda' => ['nullable', 'boolean'],
            'reminder_settings.send_pre_reads' => ['nullable', 'boolean'],
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
            'meeting_type.required' => 'Meeting type must be specified.',
            'meeting_type.in' => 'Invalid meeting type selected.',
            'scheduled_date.required' => 'Meeting date is required.',
            'scheduled_date.after' => 'Meeting date must be in the future.',
            'scheduled_time.required' => 'Meeting time is required.',
            'scheduled_time.date_format' => 'Meeting time must be in HH:MM format.',
            'duration.required' => 'Meeting duration is required.',
            'duration.min' => 'Meeting duration must be at least 15 minutes.',
            'duration.max' => 'Meeting duration cannot exceed 4 hours.',
            'location.required_without' => 'Physical location is required when no virtual meeting link is provided.',
            'virtual_meeting_link.required_without' => 'Virtual meeting link is required when no physical location is provided.',
            'virtual_meeting_link.url' => 'Virtual meeting link must be a valid URL.',
            'agenda.required' => 'Meeting agenda is required.',
            'agenda.min' => 'Agenda must be at least 50 characters long.',
            'agenda.max' => 'Agenda cannot exceed 2000 characters.',
            'changes.required' => 'At least one change must be included in the CAB meeting.',
            'changes.min' => 'At least one change must be included in the CAB meeting.',
            'changes.*.exists' => 'One or more selected changes do not exist.',
            'attendees.required' => 'CAB meeting attendees must be specified.',
            'attendees.min' => 'At least 3 attendees are required for a CAB meeting.',
            'attendees.*.exists' => 'One or more selected attendees do not exist.',
            'optional_attendees.*.exists' => 'One or more optional attendees do not exist.',
            'chairperson.required' => 'Meeting chairperson must be specified.',
            'chairperson.exists' => 'Selected chairperson does not exist.',
            'secretary.required' => 'Meeting secretary must be specified.',
            'secretary.exists' => 'Selected secretary does not exist.',
            'secretary.different' => 'Secretary must be different from chairperson.',
            'pre_read_documents.max' => 'Maximum 10 pre-read documents allowed.',
            'pre_read_documents.*.mimes' => 'Pre-read documents must be PDF, Word, Excel, or PowerPoint files.',
            'pre_read_documents.*.max' => 'Each pre-read document must not exceed 20MB.',
            'voting_required.required' => 'Please specify if voting is required.',
            'quorum_percentage.required_if' => 'Quorum percentage is required when voting is enabled.',
            'quorum_percentage.min' => 'Quorum must be at least 50%.',
            'quorum_percentage.max' => 'Quorum cannot exceed 100%.',
            'reminder_settings.days_before.required_with' => 'Days before reminder is required.',
            'reminder_settings.days_before.min' => 'Reminder must be sent at least 1 day before.',
            'reminder_settings.days_before.max' => 'Reminder cannot be sent more than 7 days before.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('voting_required')) {
            $this->merge([
                'voting_required' => filter_var($this->voting_required, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('reminder_settings')) {
            $reminderSettings = $this->reminder_settings;
            if (isset($reminderSettings['send_agenda'])) {
                $reminderSettings['send_agenda'] = filter_var($reminderSettings['send_agenda'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($reminderSettings['send_pre_reads'])) {
                $reminderSettings['send_pre_reads'] = filter_var($reminderSettings['send_pre_reads'], FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['reminder_settings' => $reminderSettings]);
        }
    }
}