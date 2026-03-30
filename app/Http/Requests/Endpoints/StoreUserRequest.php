<?php

namespace App\Http\Requests\Endpoints;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',

            'company_id' => 'required|exists:companies,id',

            'email' => 'required|email|unique:users,email',

            'phone' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],

            'password' => 'required|string|min:6',

            'is_super_admin' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',

            'company_id.required' => 'Debes seleccionar una empresa.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Formato de correo inválido.',
            'email.unique' => 'Este correo ya está registrado.',

            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'Formato inválido (ej: +521234567890).',
            'phone.min' => 'Debe tener al menos 10 dígitos.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'Mínimo 6 caracteres.',

            'is_super_admin.required' => 'Debes seleccionar el tipo de usuario.',
            'is_super_admin.boolean' => 'El valor debe ser verdadero o falso.',
        ];
    }
}