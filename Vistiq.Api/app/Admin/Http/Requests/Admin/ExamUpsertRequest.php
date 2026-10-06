<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use VistiqCore\Services\ExamPublishingService;

class ExamUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['sections', 'policies'] as $key) {
            if ($this->has($key.'_json')) {
                $data = json_decode($this->input($key.'_json'), true);
                if (! is_array($data)) {
                    throw ValidationException::withMessages([$key => 'JSON không hợp lệ.']);
                } $this->merge([$key => $data]);
            }
        }
    }

    public function rules(): array
    {
        $rules = ['title' => 'required|string|max:180', 'description' => 'nullable|string|max:10000', 'provider' => 'nullable|string|max:120', 'exam_type_id' => 'required|integer|exists:exam_types,id', 'mode' => 'required|in:full,mini,skill', 'variant' => 'nullable|in:academic,general', 'score_scale_id' => 'nullable|integer|exists:score_scales,id', 'opens_at' => 'nullable|date', 'closes_at' => 'nullable|date|after:opens_at', 'revision_counter' => 'nullable|integer|min:0', 'sections' => 'present|array|max:20', 'sections.*.exam_part_id' => 'required|integer|exists:exam_parts,id', 'sections.*.question_ids' => 'present|array|max:200', 'sections.*.question_ids.*' => 'integer|exists:question_bank_items,id', 'sections.*.duration_minutes' => 'nullable|integer|min:1|max:240', 'policies' => 'present|array:'.implode(',', array_keys(ExamPublishingService::POLICIES)), 'policies.duration_minutes' => 'required|integer|min:1|max:480', 'policies.navigation' => 'nullable|in:free,linear', 'policies.answer_review' => 'nullable|in:after_each,after_submit,never,after_close', 'policies.explanation_review' => 'nullable|in:after_each,after_submit,never,after_close', 'policies.warning_threshold' => 'nullable|integer|min:0|max:100', 'policies.auto_submit_threshold' => 'nullable|integer|min:0|max:100'];
        foreach (ExamPublishingService::POLICIES as $key => $value) {
            if (is_bool($value)) {
                $rules['policies.'.$key] = 'sometimes|boolean';
            }
        }

        return $rules;
    }
}
