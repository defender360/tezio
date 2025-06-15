<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $article = $this->route('article');
        return $article->enable_ratings && 
               $article->status === 'published' && 
               $this->user()->can('rate-knowledge-articles');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'min:10', 'max:1000'],
            'feedback_type' => ['required_with:feedback', 'nullable', Rule::in(['helpful', 'not_helpful', 'inaccurate', 'outdated', 'unclear', 'suggestion'])],
            'usefulness_score' => ['required', 'integer', 'min:1', 'max:10'],
            'clarity_score' => ['required', 'integer', 'min:1', 'max:10'],
            'completeness_score' => ['required', 'integer', 'min:1', 'max:10'],
            'accuracy_score' => ['required', 'integer', 'min:1', 'max:10'],
            'would_recommend' => ['required', 'boolean'],
            'found_solution' => ['required', 'boolean'],
            'time_spent_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'improvement_suggestions' => ['nullable', 'array', 'max:5'],
            'improvement_suggestions.*' => ['string', 'min:10', 'max:500'],
            'missing_information' => ['nullable', 'string', 'max:1000'],
            'user_expertise_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
            'usage_context' => ['nullable', Rule::in(['learning', 'troubleshooting', 'reference', 'training', 'other'])],
            'usage_context_other' => ['required_if:usage_context,other', 'nullable', 'string', 'max:100'],
            'device_type' => ['nullable', Rule::in(['desktop', 'tablet', 'mobile'])],
            'anonymous' => ['nullable', 'boolean'],
            'contact_me' => ['nullable', 'boolean'],
            'contact_reason' => ['required_if:contact_me,true', 'nullable', Rule::in(['clarification', 'more_help', 'contribution', 'error_report'])],
            'contact_method' => ['required_if:contact_me,true', 'nullable', Rule::in(['email', 'phone', 'teams'])],
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
            'rating.required' => 'Overall rating is required.',
            'rating.min' => 'Rating must be between 1 and 5 stars.',
            'rating.max' => 'Rating must be between 1 and 5 stars.',
            'feedback.min' => 'Feedback must be at least 10 characters.',
            'feedback.max' => 'Feedback cannot exceed 1000 characters.',
            'feedback_type.required_with' => 'Feedback type must be specified when providing feedback.',
            'feedback_type.in' => 'Invalid feedback type selected.',
            'usefulness_score.required' => 'Usefulness score is required.',
            'usefulness_score.min' => 'Usefulness score must be between 1 and 10.',
            'usefulness_score.max' => 'Usefulness score must be between 1 and 10.',
            'clarity_score.required' => 'Clarity score is required.',
            'clarity_score.min' => 'Clarity score must be between 1 and 10.',
            'clarity_score.max' => 'Clarity score must be between 1 and 10.',
            'completeness_score.required' => 'Completeness score is required.',
            'completeness_score.min' => 'Completeness score must be between 1 and 10.',
            'completeness_score.max' => 'Completeness score must be between 1 and 10.',
            'accuracy_score.required' => 'Accuracy score is required.',
            'accuracy_score.min' => 'Accuracy score must be between 1 and 10.',
            'accuracy_score.max' => 'Accuracy score must be between 1 and 10.',
            'would_recommend.required' => 'Please indicate if you would recommend this article.',
            'found_solution.required' => 'Please indicate if you found the solution you were looking for.',
            'time_spent_minutes.min' => 'Time spent must be at least 1 minute.',
            'time_spent_minutes.max' => 'Time spent cannot exceed 120 minutes.',
            'improvement_suggestions.max' => 'Maximum 5 improvement suggestions allowed.',
            'improvement_suggestions.*.min' => 'Each suggestion must be at least 10 characters.',
            'improvement_suggestions.*.max' => 'Each suggestion cannot exceed 500 characters.',
            'missing_information.max' => 'Missing information description cannot exceed 1000 characters.',
            'user_expertise_level.required' => 'Your expertise level is required.',
            'user_expertise_level.in' => 'Invalid expertise level selected.',
            'usage_context.in' => 'Invalid usage context selected.',
            'usage_context_other.required_if' => 'Please specify the usage context.',
            'usage_context_other.max' => 'Usage context description cannot exceed 100 characters.',
            'device_type.in' => 'Invalid device type selected.',
            'contact_reason.required_if' => 'Contact reason is required when requesting contact.',
            'contact_reason.in' => 'Invalid contact reason selected.',
            'contact_method.required_if' => 'Contact method is required when requesting contact.',
            'contact_method.in' => 'Invalid contact method selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('would_recommend')) {
            $this->merge([
                'would_recommend' => filter_var($this->would_recommend, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('found_solution')) {
            $this->merge([
                'found_solution' => filter_var($this->found_solution, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('anonymous')) {
            $this->merge([
                'anonymous' => filter_var($this->anonymous, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('contact_me')) {
            $this->merge([
                'contact_me' => filter_var($this->contact_me, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Default anonymous to false if not provided
        if (!$this->has('anonymous')) {
            $this->merge(['anonymous' => false]);
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
            // Check if user has already rated this article
            $article = $this->route('article');
            $existingRating = $article->ratings()
                ->where('user_id', $this->user()->id)
                ->first();

            if ($existingRating && !$this->isMethod('PUT')) {
                $validator->errors()->add('rating', 'You have already rated this article. Use PUT method to update your rating.');
            }
        });
    }
}