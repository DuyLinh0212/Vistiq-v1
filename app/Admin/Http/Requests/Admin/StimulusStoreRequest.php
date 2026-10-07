<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StimulusStoreRequest extends FormRequest
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
    protected function prepareForValidation(): void
    {
        if ($this->filled('metadata_json')) {
            $metadata = json_decode($this->input('metadata_json'), true);
            if (! is_array($metadata)) {
                throw ValidationException::withMessages(['metadata' => 'JSON metadata không hợp lệ.']);
            } $this->merge(['metadata' => $metadata]);
        }
    }

    public function rules(): array
    {
        return [
            'stimulus_type' => ['required', 'string', 'in:reading,audio'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000'],
            'transcript' => ['nullable', 'string', 'max:50000'],
            'asset_path' => ['nullable', 'string', 'url', 'max:2048'],
            'media' => ['nullable', 'file', 'max:51200', 'mimes:mp3,wav,m4a,ogg,jpg,jpeg,png,webp'],
            'accent' => ['nullable', 'string', 'max:40'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'status' => ['nullable', 'in:draft'],
            'metadata' => 'nullable|array', 'metadata.asset_id' => 'nullable|integer|exists:media_assets,id', 'revision_counter' => 'nullable|integer|min:0',
        ];
    }
}
