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
            'reset_token' => ['required', 'string', 'uuid'],
            'password'    => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reset_token.required' => 'El token de recuperación es obligatorio',
            'reset_token.uuid'     => 'El token de recuperación no es válido',
            'password.required'    => 'La contraseña es obligatoria',
            'password.confirmed'   => 'Las contraseñas no coinciden',
            'password.min'         => 'La contraseña debe tener al menos 8 caracteres',
            'password.mixed_case'  => 'La contraseña debe contener mayúsculas y minúsculas',
            'password.numbers'     => 'La contraseña debe contener números',
            'password.symbols'     => 'La contraseña debe contener caracteres especiales',
        ];
    }
}
