<?php

namespace VistiqAdmin\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return ['password' => 'required|current_password:web', 'include_schema' => 'sometimes|boolean', 'include_data' => 'sometimes|boolean', 'file' => 'required_if:restore,1|sometimes|file|mimes:txt,sql|extensions:sql|max:102400'];
    }
}
