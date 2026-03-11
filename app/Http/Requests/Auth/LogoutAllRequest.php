<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LogoutAllRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria',
            'password.current_password' => 'La contraseña es incorrecta',
        ];
    }
}
