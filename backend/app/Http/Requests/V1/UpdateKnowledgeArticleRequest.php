<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $article = $this->route('article');
        return $this->user()->can('update-knowledge-articles') || 
               $this->user()->id === $article->created_by;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('article');
        
        $rules = [
            'title' => ['sometimes', 'string', 'max:255', Rule::unique('knowledge_articles', 'title')->ignore($article->id)],
            'summary' => ['sometimes', 'string', 'min:50', 'max:500'],
            'content' => ['sometimes', 'string', 'min:100'],
            'category_id' => ['sometimes', 'integer', 'exists:knowledge_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'article_type' => ['sometimes', Rule::in(['how_to', 'troubleshooting', 'reference', 'faq', 'best_practice', 'policy'])],
            'audience' => ['sometimes', Rule::in(['end_users', 'technicians', 'managers', 'all'])],
            'status' => ['sometimes', Rule::in(['draft', 'review', 'published', 'archived'])],
            'keywords' => ['sometimes', 'array', 'min:3', 'max:15'],
            'keywords.*' => ['string', 'max:50'],
            'related_articles' => ['nullable', 'array', 'max:10'],
            'related_articles.*' => ['integer', 'exists:knowledge_articles,id', 'not_in:' . $article->id],
            'related_incidents' => ['nullable', 'array'],
            'related_incidents.*' => ['integer', 'exists:incidents,id'],
            'related_problems' => ['nullable', 'array'],
            'related_problems.*' => ['integer', 'exists:problems,id'],
            'related_changes' => ['nullable', 'array'],
            'related_changes.*' => ['integer', 'exists:changes,id'],
            'author_notes' => ['nullable', 'string', 'max:1000'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'review_cycle_days' => ['nullable', 'integer', 'min:30', 'max:365'],
            'update_reason' => ['required', 'string', 'min:20', 'max:500'],
            'major_update' => ['required', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata.version' => ['nullable', 'string', 'max:20'],
            'metadata.language' => ['nullable', 'string', 'max:10'],
            'metadata.difficulty_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
            'metadata.estimated_reading_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'metadata.prerequisites' => ['nullable', 'array'],
            'metadata.prerequisites.*' => ['string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,mp4,mov', 'max:51200'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'exists:attachments,id'],
            'access_control' => ['nullable', 'array'],
            'access_control.visibility' => ['nullable', Rule::in(['public', 'internal', 'restricted'])],
            'access_control.allowed_groups' => ['nullable', 'array'],
            'access_control.allowed_groups.*' => ['string', 'max:100'],
            'access_control.allowed_users' => ['nullable', 'array'],
            'access_control.allowed_users.*' => ['integer', 'exists:users,id'],
            'seo_metadata' => ['nullable', 'array'],
            'seo_metadata.meta_title' => ['nullable', 'string', 'max:60'],
            'seo_metadata.meta_description' => ['nullable', 'string', 'max:160'],
            'seo_metadata.slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('knowledge_articles', 'slug')->ignore($article->id)],
            'enable_comments' => ['nullable', 'boolean'],
            'enable_ratings' => ['nullable', 'boolean'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.notify_on_comment' => ['nullable', 'boolean'],
            'notification_settings.notify_on_rating' => ['nullable', 'boolean'],
            'notification_settings.notify_on_update' => ['nullable', 'boolean'],
            'review_status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'needs_revision'])],
            'review_comments' => ['required_if:review_status,rejected,needs_revision', 'nullable', 'string', 'min:20', 'max:1000'],
        ];

        // Restrict certain fields based on article status
        if ($article->status === 'published') {
            // Published articles have more restrictions
            $restrictedFields = ['category_id', 'article_type'];
            foreach ($restrictedFields as $field) {
                if (isset($rules[$field])) {
                    $rules[$field][] = 'prohibited';
                }
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
            'title.unique' => 'An article with this title already exists.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'summary.min' => 'Summary must be at least 50 characters.',
            'summary.max' => 'Summary cannot exceed 500 characters.',
            'content.min' => 'Content must be at least 100 characters.',
            'category_id.exists' => 'Selected category does not exist.',
            'category_id.prohibited' => 'Cannot change category of published articles.',
            'subcategory_id.exists' => 'Selected subcategory does not exist.',
            'article_type.in' => 'Invalid article type selected.',
            'article_type.prohibited' => 'Cannot change type of published articles.',
            'audience.in' => 'Invalid audience type selected.',
            'status.in' => 'Invalid status selected.',
            'keywords.min' => 'At least 3 keywords must be provided.',
            'keywords.max' => 'Maximum 15 keywords allowed.',
            'keywords.*.max' => 'Each keyword cannot exceed 50 characters.',
            'related_articles.max' => 'Maximum 10 related articles allowed.',
            'related_articles.*.exists' => 'One or more related articles do not exist.',
            'related_articles.*.not_in' => 'Cannot relate article to itself.',
            'related_incidents.*.exists' => 'One or more related incidents do not exist.',
            'related_problems.*.exists' => 'One or more related problems do not exist.',
            'related_changes.*.exists' => 'One or more related changes do not exist.',
            'author_notes.max' => 'Author notes cannot exceed 1000 characters.',
            'expiry_date.after' => 'Expiry date must be in the future.',
            'review_cycle_days.min' => 'Review cycle must be at least 30 days.',
            'review_cycle_days.max' => 'Review cycle cannot exceed 365 days.',
            'update_reason.required' => 'Update reason is required.',
            'update_reason.min' => 'Update reason must be at least 20 characters.',
            'major_update.required' => 'Please specify if this is a major update.',
            'metadata.version.max' => 'Version cannot exceed 20 characters.',
            'metadata.language.max' => 'Language code cannot exceed 10 characters.',
            'metadata.difficulty_level.in' => 'Invalid difficulty level.',
            'metadata.estimated_reading_time.min' => 'Reading time must be at least 1 minute.',
            'metadata.estimated_reading_time.max' => 'Reading time cannot exceed 120 minutes.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, Word, Excel, Images, Videos.',
            'attachments.*.max' => 'Each attachment must not exceed 50MB.',
            'remove_attachments.*.exists' => 'One or more attachments to remove were not found.',
            'access_control.visibility.in' => 'Invalid visibility setting.',
            'access_control.allowed_users.*.exists' => 'One or more allowed users do not exist.',
            'seo_metadata.meta_title.max' => 'SEO title cannot exceed 60 characters.',
            'seo_metadata.meta_description.max' => 'SEO description cannot exceed 160 characters.',
            'seo_metadata.slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'seo_metadata.slug.unique' => 'This slug is already in use.',
            'review_status.in' => 'Invalid review status.',
            'review_comments.required_if' => 'Review comments are required when rejecting or requesting revision.',
            'review_comments.min' => 'Review comments must be at least 20 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('major_update')) {
            $this->merge([
                'major_update' => filter_var($this->major_update, FILTER_VALIDATE_BOOLEAN),
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
    }
}