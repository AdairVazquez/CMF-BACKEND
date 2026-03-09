<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:36'],
            'recovery_code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token es obligatorio',
            'token.size' => 'El token no es válido',
            'recovery_code.required' => 'El código de recuperación es obligatorio',
        ];
    }
}
