<?php

namespace VistiqAdmin\Http\Requests\Admin\Identity\Authentication;

use Illuminate\Foundation\Http\FormRequest;

class PasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['email' => 'required|email|max:254'];
        if ($this->routeIs('admin.login.store', 'api.admin.login.store')) {
            $rules['password'] = 'required|string';
            $rules['remember'] = 'sometimes|boolean';
        }
        if ($this->routeIs('admin.password.update', 'api.admin.password.update')) {
            $rules['token'] = 'required|string';
            $rules['password'] = 'required|string|min:10|max:128|confirmed';
        }

        return $rules;
    }
}
