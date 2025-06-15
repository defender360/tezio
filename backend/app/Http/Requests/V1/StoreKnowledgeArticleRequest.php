<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKnowledgeArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-knowledge-articles');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:knowledge_articles,title'],
            'summary' => ['required', 'string', 'min:50', 'max:500'],
            'content' => ['required', 'string', 'min:100'],
            'category_id' => ['required', 'integer', 'exists:knowledge_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'article_type' => ['required', Rule::in(['how_to', 'troubleshooting', 'reference', 'faq', 'best_practice', 'policy'])],
            'audience' => ['required', Rule::in(['end_users', 'technicians', 'managers', 'all'])],
            'status' => ['required', Rule::in(['draft', 'review', 'published', 'archived'])],
            'keywords' => ['required', 'array', 'min:3', 'max:15'],
            'keywords.*' => ['string', 'max:50'],
            'related_articles' => ['nullable', 'array', 'max:10'],
            'related_articles.*' => ['integer', 'exists:knowledge_articles,id'],
            'related_incidents' => ['nullable', 'array'],
            'related_incidents.*' => ['integer', 'exists:incidents,id'],
            'related_problems' => ['nullable', 'array'],
            'related_problems.*' => ['integer', 'exists:problems,id'],
            'related_changes' => ['nullable', 'array'],
            'related_changes.*' => ['integer', 'exists:changes,id'],
            'author_notes' => ['nullable', 'string', 'max:1000'],
            'review_required' => ['required', 'boolean'],
            'reviewers' => ['required_if:review_required,true', 'nullable', 'array', 'min:1'],
            'reviewers.*' => ['integer', 'exists:users,id'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'review_cycle_days' => ['nullable', 'integer', 'min:30', 'max:365'],
            'metadata' => ['nullable', 'array'],
            'metadata.version' => ['nullable', 'string', 'max:20'],
            'metadata.language' => ['nullable', 'string', 'max:10'],
            'metadata.difficulty_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
            'metadata.estimated_reading_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'metadata.prerequisites' => ['nullable', 'array'],
            'metadata.prerequisites.*' => ['string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,mp4,mov', 'max:51200'],
            'access_control' => ['nullable', 'array'],
            'access_control.visibility' => ['nullable', Rule::in(['public', 'internal', 'restricted'])],
            'access_control.allowed_groups' => ['nullable', 'array'],
            'access_control.allowed_groups.*' => ['string', 'max:100'],
            'access_control.allowed_users' => ['nullable', 'array'],
            'access_control.allowed_users.*' => ['integer', 'exists:users,id'],
            'seo_metadata' => ['nullable', 'array'],
            'seo_metadata.meta_title' => ['nullable', 'string', 'max:60'],
            'seo_metadata.meta_description' => ['nullable', 'string', 'max:160'],
            'seo_metadata.slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:knowledge_articles,slug'],
            'enable_comments' => ['nullable', 'boolean'],
            'enable_ratings' => ['nullable', 'boolean'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.notify_on_comment' => ['nullable', 'boolean'],
            'notification_settings.notify_on_rating' => ['nullable', 'boolean'],
            'notification_settings.notify_on_update' => ['nullable', 'boolean'],
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
            'title.required' => 'Article title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'title.unique' => 'An article with this title already exists.',
            'summary.required' => 'Article summary is required.',
            'summary.min' => 'Summary must be at least 50 characters.',
            'summary.max' => 'Summary cannot exceed 500 characters.',
            'content.required' => 'Article content is required.',
            'content.min' => 'Content must be at least 100 characters.',
            'category_id.required' => 'Article category must be selected.',
            'category_id.exists' => 'Selected category does not exist.',
            'subcategory_id.exists' => 'Selected subcategory does not exist.',
            'article_type.required' => 'Article type must be specified.',
            'article_type.in' => 'Invalid article type selected.',
            'audience.required' => 'Target audience must be specified.',
            'audience.in' => 'Invalid audience type selected.',
            'status.required' => 'Article status must be specified.',
            'status.in' => 'Invalid status selected.',
            'keywords.required' => 'At least 3 keywords are required.',
            'keywords.min' => 'At least 3 keywords must be provided.',
            'keywords.max' => 'Maximum 15 keywords allowed.',
            'keywords.*.max' => 'Each keyword cannot exceed 50 characters.',
            'related_articles.max' => 'Maximum 10 related articles allowed.',
            'related_articles.*.exists' => 'One or more related articles do not exist.',
            'related_incidents.*.exists' => 'One or more related incidents do not exist.',
            'related_problems.*.exists' => 'One or more related problems do not exist.',
            'related_changes.*.exists' => 'One or more related changes do not exist.',
            'author_notes.max' => 'Author notes cannot exceed 1000 characters.',
            'review_required.required' => 'Please specify if review is required.',
            'reviewers.required_if' => 'At least one reviewer must be specified when review is required.',
            'reviewers.min' => 'At least one reviewer must be specified.',
            'reviewers.*.exists' => 'One or more reviewers do not exist.',
            'expiry_date.after' => 'Expiry date must be in the future.',
            'review_cycle_days.min' => 'Review cycle must be at least 30 days.',
            'review_cycle_days.max' => 'Review cycle cannot exceed 365 days.',
            'metadata.version.max' => 'Version cannot exceed 20 characters.',
            'metadata.language.max' => 'Language code cannot exceed 10 characters.',
            'metadata.difficulty_level.in' => 'Invalid difficulty level.',
            'metadata.estimated_reading_time.min' => 'Reading time must be at least 1 minute.',
            'metadata.estimated_reading_time.max' => 'Reading time cannot exceed 120 minutes.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Excel, Images, Videos.',
            'attachments.*.max' => 'Each attachment must not exceed 50MB.',
            'access_control.visibility.in' => 'Invalid visibility setting.',
            'access_control.allowed_users.*.exists' => 'One or more allowed users do not exist.',
            'seo_metadata.meta_title.max' => 'SEO title cannot exceed 60 characters.',
            'seo_metadata.meta_description.max' => 'SEO description cannot exceed 160 characters.',
            'seo_metadata.slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'seo_metadata.slug.unique' => 'This slug is already in use.',
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

        if ($this->has('enable_comments')) {
            $this->merge([
                'enable_comments' => filter_var($this->enable_comments, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('enable_ratings')) {
            $this->merge([
                'enable_ratings' => filter_var($this->enable_ratings, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('notification_settings')) {
            $settings = $this->notification_settings;
            foreach (['notify_on_comment', 'notify_on_rating', 'notify_on_update'] as $field) {
                if (isset($settings[$field])) {
                    $settings[$field] = filter_var($settings[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['notification_settings' => $settings]);
        }

        // Auto-generate slug from title if not provided
        if (!$this->has('seo_metadata.slug') && $this->has('title')) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->title), '-'));
            $seoMetadata = $this->seo_metadata ?? [];
            $seoMetadata['slug'] = $slug;
            $this->merge(['seo_metadata' => $seoMetadata]);
        }
    }
}