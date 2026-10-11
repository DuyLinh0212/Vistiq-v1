<?php

namespace VistiqAdmin\Http\Requests\Admin\Learning\Knowledge;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class KnowledgeUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('dictionary_entry_ids_text') && ! $this->has('dictionary_entry_ids')) {
            $this->merge(['dictionary_entry_ids' => preg_split('/[\\s,]+/', trim($this->input('dictionary_entry_ids_text') ?? ''), -1, PREG_SPLIT_NO_EMPTY)]);
        }

        foreach (['dictionary_entry_ids', 'examples', 'relations'] as $field) {
            if ($this->boolean($field.'_present') && ! $this->has($field)) {
                $this->merge([$field => []]);
            }
        }

        foreach (['examples', 'relations', 'grammar_rules'] as $field) {
            if (! $this->has($field) && $this->filled($field.'_json')) {
                $data = json_decode($this->input($field.'_json'), true);
                if (! is_array($data)) {
                    throw ValidationException::withMessages([$field => 'JSON không hợp lệ.']);
                }

                $this->merge([$field => $data]);
            }

            $rows = $this->input($field);
            if (is_array($rows)) {
                $this->merge([$field => array_values(array_filter($rows, function (mixed $row) use ($field): bool {
                    if (! is_array($row)) {
                        return false;
                    }

                    return match ($field) {
                        'examples' => filled($row['english'] ?? null) || filled($row['vietnamese'] ?? null),
                        'relations' => filled($row['id'] ?? null) && filled($row['type'] ?? null),
                        'grammar_rules' => filled($row['title'] ?? null) || filled($row['formula'] ?? null) || filled($row['explanation'] ?? null),
                        default => true,
                    };
                }))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:vocabulary,collocation,phrasal_verb,idiom,grammar',
            'english' => 'required|string|max:255',
            'meaning_vi' => 'required_unless:type,grammar|nullable|string|max:10000',
            'definition_en' => 'nullable|string|max:10000',
            'ipa' => 'nullable|string|max:100',
            'grammatical_pattern' => 'nullable|string|max:255',
            'dictionary_entry_ids' => 'nullable|array|max:30',
            'dictionary_entry_ids.*' => 'integer|exists:dictionary_entries,id',
            'dictionary_entry_ids_present' => 'sometimes|boolean',
            'base_dictionary_entry_id' => 'required_if:type,phrasal_verb|nullable|integer|exists:dictionary_entries,id',
            'particle' => 'required_if:type,phrasal_verb|nullable|string|max:80',
            'register' => 'nullable|in:formal,informal,neutral',
            'usage_note' => 'nullable|string|max:10000',
            'part_of_speech_id' => 'nullable|integer|exists:parts_of_speech,id',
            'proficiency_level_id' => 'nullable|integer|exists:proficiency_levels,id',
            'tags' => 'nullable|string|max:500',
            'content' => 'nullable|string|max:100000',
            'summary' => 'nullable|string|max:10000',
            'grammar_category_id' => 'required_if:type,grammar|nullable|integer|exists:grammar_categories,id',
            'examples' => 'nullable|array|max:50',
            'examples_present' => 'sometimes|boolean',
            'examples.*.english' => 'required|string|max:2000',
            'examples.*.vietnamese' => 'nullable|string|max:2000',
            'relations' => 'nullable|array|max:50',
            'relations_present' => 'sometimes|boolean',
            'relations.*.id' => 'required|integer|exists:learning_items,id',
            'relations.*.type' => 'required|in:synonym,antonym,related,derived',
            'relevance' => 'nullable|string|max:10000',
            'grammar_rules' => 'nullable|array|max:50',
            'grammar_rules.*.title' => 'required|string|max:180',
            'grammar_rules.*.formula' => 'nullable|string|max:1000',
            'grammar_rules.*.explanation' => 'nullable|string|max:10000',
            'revision_counter' => 'nullable|integer|min:0',
        ];
    }
}
