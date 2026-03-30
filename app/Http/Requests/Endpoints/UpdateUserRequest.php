<?php

namespace App\Http\Requests\Endpoints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : $user;
        return [
            'name' => 'sometimes|required|string|max:255',
            'company_id' => 'sometimes|required|exists:companies,id',
            'password' => 'sometimes|nullable|string|min:6',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/',
            ],
            'is_super_admin' => 'sometimes|required|boolean',
            'is_active' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',

            'company_id.required' => 'Debes seleccionar una empresa.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Formato de correo invalido.',
            'email.unique' => 'Este correo ya esta registrado.',

            'phone.required' => 'El telefono es obligatorio.',
            'phone.regex' => 'Formato invalido (ej: +521234567890).',
            'phone.min' => 'Debe tener al menos 10 digitos.',

            'password.required' => 'La contrasena es obligatoria.',
            'password.min' => 'Minimo 6 caracteres.',

            'is_super_admin.required' => 'Debes seleccionar el tipo de usuario.',
            'is_super_admin.boolean' => 'El valor debe ser verdadero o falso.',

            'is_active.required' => 'Debes indicar si el usuario esta activo.',
            'is_active.boolean' => 'El valor debe ser verdadero o falso.',
        ];
    }
}
