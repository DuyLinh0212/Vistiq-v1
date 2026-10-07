<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class QuestionUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('options_values')) {
            $optionValues = $this->input('options_values', []);
            if (! is_array($optionValues)) {
                throw ValidationException::withMessages(['options' => 'Danh sách phương án không hợp lệ.']);
            }
            $this->merge(['options' => array_filter($optionValues, fn ($value): bool => trim((string) $value) !== '')]);
        }
        if ($this->has('options_json')) {
            $options = json_decode((string) $this->input('options_json'), true);
            if (! is_array($options)) {
                throw ValidationException::withMessages(['options' => 'Options phải là JSON object.']);
            } $this->merge(['options' => $options]);
        }
        if ($this->has('correct_options_text')) {
            $this->merge(['correct_options' => preg_split('/[\\s,|]+/', trim($this->input('correct_options_text')), -1, PREG_SPLIT_NO_EMPTY)]);
        }
        if ($this->has('accepted_answers_text')) {
            $this->merge(['accepted_answers' => preg_split('/\\R/', trim($this->input('accepted_answers_text')), -1, PREG_SPLIT_NO_EMPTY)]);
        }
        $metadata = $this->input('metadata', []);
        if (! is_array($metadata)) {
            throw ValidationException::withMessages(['metadata' => 'Thông tin bổ sung không hợp lệ.']);
        }
        if ($this->filled('metadata_json')) {
            $metadata = json_decode($this->string('metadata_json')->toString(), true);
            if (! is_array($metadata)) {
                throw ValidationException::withMessages(['metadata_json' => 'Metadata phải là JSON object hợp lệ.']);
            }
        }
        foreach (['answer_map_text', 'blanks_text', 'metadata.labels_text'] as $field) {
            if (! $this->has($field)) {
                continue;
            }
            $metadataKey = match ($field) {
                'answer_map_text' => 'answer_map',
                'blanks_text' => 'blanks',
                default => 'labels',
            };
            $metadata[$metadataKey] = $this->parseStructuredRows((string) $this->input($field), $metadataKey !== 'blanks');
            if ($field === 'metadata.labels_text') {
                unset($metadata['labels_text']);
            }
        }
        if ($this->has('follow_ups_text')) {
            $metadata['follow_ups'] = preg_split('/\\R/', trim((string) $this->input('follow_ups_text')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        if ($this->has('metadata.rubric_text')) {
            $metadata['rubric'] = [];
            foreach (preg_split('/\\R/', trim((string) $this->input('metadata.rubric_text')), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
                [$criterion, $guidance] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                if ($criterion !== '' && $guidance !== '') {
                    $metadata['rubric'][$criterion] = $guidance;
                }
            }
            unset($metadata['rubric_text']);
        }
        if ($this->has('metadata.follow_ups_text')) {
            $metadata['follow_ups'] = preg_split('/\\R/', trim((string) $this->input('metadata.follow_ups_text')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            unset($metadata['follow_ups_text']);
        }
        $this->merge(['status' => 'draft', 'metadata' => $metadata]);
    }

    public function rules(): array
    {
        return [
            'exam_part_id' => 'required|integer|exists:exam_parts,id', 'question_type_id' => 'required|integer|exists:question_types,id', 'question_difficulty_id' => 'required|integer|exists:question_difficulties,id',
            'learning_stimulus_id' => 'nullable|integer|exists:learning_stimuli,id', 'prompt' => 'nullable|string|max:10000', 'explanation' => 'nullable|string|max:10000', 'status' => 'required|in:draft',
            'options' => 'nullable|array|max:30', 'options.*' => 'nullable|string|max:2000', 'correct_option' => 'nullable|string|max:12', 'correct_options' => 'nullable|array|max:30', 'correct_options.*' => 'string|max:12',
            'accepted_answers' => 'nullable|array|max:50', 'accepted_answers.*' => 'nullable|string|max:500', 'tags' => 'nullable|string|max:500', 'metadata' => 'nullable|array',
            'metadata.max_words' => 'nullable|integer|min:0|max:100', 'metadata.max_numbers' => 'nullable|integer|min:0|max:20', 'metadata.image_id' => 'nullable|integer|exists:media_assets,id', 'metadata.audio_id' => 'nullable|integer|exists:media_assets,id',
            'metadata.min_words' => 'nullable|integer|min:1|max:10000', 'metadata.variant' => 'nullable|in:academic,general', 'metadata.cue_card' => 'nullable|string|max:10000', 'metadata.model_answer' => 'nullable|string|max:10000',
            'metadata.answer_map' => 'nullable|array|max:100', 'metadata.answer_map.*' => 'string|max:30', 'metadata.blanks' => 'nullable|array|max:100', 'metadata.blanks.*' => 'array:label,accepted_answers', 'metadata.blanks.*.label' => 'nullable|string|max:500', 'metadata.blanks.*.accepted_answers' => 'required_with:metadata.blanks|array|min:1|max:50', 'metadata.blanks.*.accepted_answers.*' => 'string|max:500',
            'metadata.follow_ups' => 'nullable|array|max:20', 'metadata.follow_ups.*' => 'string|max:2000', 'metadata.labels' => 'nullable|array|max:100', 'metadata.labels.*' => 'string|max:1000', 'metadata.rubric' => 'nullable|array', 'metadata.rubric.*' => 'nullable|string|max:2000',
            'question_group_id' => 'nullable|integer|exists:question_groups,id', 'group_order' => 'nullable|integer|min:0|max:200',
            'revision_counter' => 'nullable|integer|min:0',
        ];
    }

    /** @return array<string, mixed> */
    private function parseStructuredRows(string $input, bool $keyValue): array
    {
        $rows = [];
        foreach (preg_split('/\\R/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            [$key, $value] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($key === '' || $value === '') {
                continue;
            }
            if ($keyValue) {
                $rows[$key] = $value;
            } else {
                $rows[(string) (count($rows) + 1)] = [
                    'label' => $key,
                    'accepted_answers' => array_values(array_filter(array_map('trim', explode(';', $value)))),
                ];
            }
        }

        return $rows;
    }
}
