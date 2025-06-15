<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-knowledge-categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:knowledge_categories,name'],
            'description' => ['required', 'string', 'min:20', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata.display_mode' => ['nullable', Rule::in(['grid', 'list', 'cards'])],
            'metadata.articles_per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'metadata.sort_order' => ['nullable', Rule::in(['alphabetical', 'popularity', 'date_created', 'date_updated', 'custom'])],
            'metadata.show_article_count' => ['nullable', 'boolean'],
            'metadata.show_subcategories' => ['nullable', 'boolean'],
            'metadata.allow_article_submission' => ['nullable', 'boolean'],
            'access_control' => ['nullable', 'array'],
            'access_control.visibility' => ['nullable', Rule::in(['public', 'internal', 'restricted'])],
            'access_control.allowed_groups' => ['nullable', 'array'],
            'access_control.allowed_groups.*' => ['string', 'max:100'],
            'access_control.allowed_users' => ['nullable', 'array'],
            'access_control.allowed_users.*' => ['integer', 'exists:users,id'],
            'access_control.inherit_parent_permissions' => ['nullable', 'boolean'],
            'seo_metadata' => ['nullable', 'array'],
            'seo_metadata.meta_title' => ['nullable', 'string', 'max:60'],
            'seo_metadata.meta_description' => ['nullable', 'string', 'max:160'],
            'seo_metadata.meta_keywords' => ['nullable', 'array', 'max:10'],
            'seo_metadata.meta_keywords.*' => ['string', 'max:50'],
            'seo_metadata.slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:knowledge_categories,slug'],
            'default_article_template' => ['nullable', 'string', 'max:10000'],
            'custom_fields' => ['nullable', 'array', 'max:10'],
            'custom_fields.*' => ['required', 'array'],
            'custom_fields.*.name' => ['required', 'string', 'max:50'],
            'custom_fields.*.type' => ['required', Rule::in(['text', 'number', 'date', 'select', 'multiselect', 'boolean'])],
            'custom_fields.*.required' => ['required', 'boolean'],
            'custom_fields.*.options' => ['required_if:custom_fields.*.type,select,multiselect', 'nullable', 'array'],
            'custom_fields.*.options.*' => ['string', 'max:100'],
            'custom_fields.*.validation_rules' => ['nullable', 'array'],
            'approval_workflow' => ['nullable', 'array'],
            'approval_workflow.enabled' => ['required', 'boolean'],
            'approval_workflow.approvers' => ['required_if:approval_workflow.enabled,true', 'nullable', 'array', 'min:1'],
            'approval_workflow.approvers.*' => ['integer', 'exists:users,id'],
            'approval_workflow.approval_levels' => ['nullable', 'integer', 'min:1', 'max:3'],
            'approval_workflow.auto_publish_after_approval' => ['nullable', 'boolean'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.notify_on_new_article' => ['nullable', 'boolean'],
            'notification_settings.notify_on_article_update' => ['nullable', 'boolean'],
            'notification_settings.notify_subscribers' => ['nullable', 'array'],
            'notification_settings.notify_subscribers.*' => ['email'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
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
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name cannot exceed 100 characters.',
            'name.unique' => 'A category with this name already exists.',
            'description.required' => 'Category description is required.',
            'description.min' => 'Description must be at least 20 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'parent_id.exists' => 'Selected parent category does not exist.',
            'icon.max' => 'Icon name cannot exceed 50 characters.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF0000).',
            'order.min' => 'Order must be a positive number.',
            'order.max' => 'Order cannot exceed 999.',
            'is_active.required' => 'Active status must be specified.',
            'metadata.display_mode.in' => 'Invalid display mode selected.',
            'metadata.articles_per_page.min' => 'Articles per page must be at least 10.',
            'metadata.articles_per_page.max' => 'Articles per page cannot exceed 100.',
            'metadata.sort_order.in' => 'Invalid sort order selected.',
            'access_control.visibility.in' => 'Invalid visibility setting.',
            'access_control.allowed_users.*.exists' => 'One or more allowed users do not exist.',
            'seo_metadata.meta_title.max' => 'SEO title cannot exceed 60 characters.',
            'seo_metadata.meta_description.max' => 'SEO description cannot exceed 160 characters.',
            'seo_metadata.meta_keywords.max' => 'Maximum 10 keywords allowed.',
            'seo_metadata.meta_keywords.*.max' => 'Each keyword cannot exceed 50 characters.',
            'seo_metadata.slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'seo_metadata.slug.unique' => 'This slug is already in use.',
            'default_article_template.max' => 'Template cannot exceed 10000 characters.',
            'custom_fields.max' => 'Maximum 10 custom fields allowed.',
            'custom_fields.*.name.required' => 'Custom field name is required.',
            'custom_fields.*.name.max' => 'Custom field name cannot exceed 50 characters.',
            'custom_fields.*.type.required' => 'Custom field type is required.',
            'custom_fields.*.type.in' => 'Invalid custom field type.',
            'custom_fields.*.required.required' => 'Please specify if custom field is required.',
            'custom_fields.*.options.required_if' => 'Options are required for select/multiselect fields.',
            'approval_workflow.enabled.required' => 'Please specify if approval workflow is enabled.',
            'approval_workflow.approvers.required_if' => 'At least one approver must be specified when workflow is enabled.',
            'approval_workflow.approvers.min' => 'At least one approver must be specified.',
            'approval_workflow.approvers.*.exists' => 'One or more approvers do not exist.',
            'approval_workflow.approval_levels.min' => 'Approval levels must be at least 1.',
            'approval_workflow.approval_levels.max' => 'Maximum 3 approval levels allowed.',
            'notification_settings.notify_subscribers.*.email' => 'Invalid email address in subscriber list.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'tags.*.max' => 'Each tag cannot exceed 30 characters.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be JPG, JPEG, PNG, or SVG format.',
            'image.max' => 'Image size cannot exceed 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('metadata')) {
            $metadata = $this->metadata;
            foreach (['show_article_count', 'show_subcategories', 'allow_article_submission'] as $field) {
                if (isset($metadata[$field])) {
                    $metadata[$field] = filter_var($metadata[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['metadata' => $metadata]);
        }

        if ($this->has('access_control.inherit_parent_permissions')) {
            $accessControl = $this->access_control;
            $accessControl['inherit_parent_permissions'] = filter_var(
                $accessControl['inherit_parent_permissions'], 
                FILTER_VALIDATE_BOOLEAN
            );
            $this->merge(['access_control' => $accessControl]);
        }

        if ($this->has('custom_fields')) {
            $customFields = $this->custom_fields;
            foreach ($customFields as $key => $field) {
                if (isset($field['required'])) {
                    $customFields[$key]['required'] = filter_var($field['required'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['custom_fields' => $customFields]);
        }

        if ($this->has('approval_workflow')) {
            $workflow = $this->approval_workflow;
            if (isset($workflow['enabled'])) {
                $workflow['enabled'] = filter_var($workflow['enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($workflow['auto_publish_after_approval'])) {
                $workflow['auto_publish_after_approval'] = filter_var(
                    $workflow['auto_publish_after_approval'], 
                    FILTER_VALIDATE_BOOLEAN
                );
            }
            $this->merge(['approval_workflow' => $workflow]);
        }

        if ($this->has('notification_settings')) {
            $settings = $this->notification_settings;
            foreach (['notify_on_new_article', 'notify_on_article_update'] as $field) {
                if (isset($settings[$field])) {
                    $settings[$field] = filter_var($settings[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['notification_settings' => $settings]);
        }

        // Auto-generate slug from name if not provided
        if (!$this->has('seo_metadata.slug') && $this->has('name')) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name), '-'));
            $seoMetadata = $this->seo_metadata ?? [];
            $seoMetadata['slug'] = $slug;
            $this->merge(['seo_metadata' => $seoMetadata]);
        }
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
            // Prevent circular parent references
            if ($this->has('parent_id') && $this->parent_id) {
                $parentId = $this->parent_id;
                $categoryId = $this->route('category')->id ?? null;
                
                if ($categoryId && $parentId == $categoryId) {
                    $validator->errors()->add('parent_id', 'A category cannot be its own parent.');
                }
                
                // Check for circular references in hierarchy
                if ($categoryId && $this->wouldCreateCircularReference($categoryId, $parentId)) {
                    $validator->errors()->add('parent_id', 'This would create a circular reference in the category hierarchy.');
                }
            }
        });
    }

    /**
     * Check if setting a parent would create a circular reference.
     *
     * @param int $categoryId
     * @param int $parentId
     * @return bool
     */
    protected function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        // This is a simplified check - in a real implementation, you would
        // traverse the category hierarchy to detect circular references
        return false;
    }
}