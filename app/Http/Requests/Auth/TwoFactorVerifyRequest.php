<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:36'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El token es obligatorio',
            'token.size' => 'El token no es válido',
            'code.required' => 'El código es obligatorio',
            'code.size' => 'El código debe tener 6 dígitos',
        ];
    }
}
