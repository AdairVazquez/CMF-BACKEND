<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'code.required' => 'El código es obligatorio',
            'code.size' => 'El código debe tener 6 dígitos',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.mixed_case' => 'La contraseña debe contener mayúsculas y minúsculas',
            'password.numbers' => 'La contraseña debe contener números',
            'password.symbols' => 'La contraseña debe contener caracteres especiales',
        ];
    }
}
