<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuestionImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.edit') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'default_question_type' => ['required', 'integer', 'exists:question_types,id'],
            'default_difficulty' => ['required', 'integer', 'exists:question_difficulties,id'],
            'default_status' => ['required', 'string', 'in:draft'],
            'import_key' => ['nullable', 'uuid'],
            'offset' => ['required', 'integer', 'min:0', 'max:2000'],
        ];
    }
}
