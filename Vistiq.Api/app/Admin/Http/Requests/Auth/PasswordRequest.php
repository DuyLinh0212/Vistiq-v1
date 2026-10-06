<?php

namespace VistiqAdmin\Http\Requests\Auth;

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
        if ($this->routeIs('login.store', 'api.login.store')) {
            $rules['password'] = 'required|string';
            $rules['remember'] = 'sometimes|boolean';
        }
        if ($this->routeIs('password.update', 'api.password.update')) {
            $rules['token'] = 'required|string';
            $rules['password'] = 'required|string|min:10|max:128|confirmed';
        }

        return $rules;
    }
}
