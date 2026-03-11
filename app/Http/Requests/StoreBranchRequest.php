<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id'  => 'required|exists:companies,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:255',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:255',
            'state'       => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'phone' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa matriz es obligatoria.',
            'company_id.exists'   => 'La empresa seleccionada no existe en nuestros registros.',
            'name.required'       => 'El nombre es obligatorio.',
            'name.string'         => 'El nombre debe ser una cadena de texto válida.',
            'name.max'            => 'El nombre no puede exceder los 255 caracteres.',
            'phone.required'      => 'El número de teléfono es indispensable.',
            'phone.min'           => 'El teléfono debe tener al menos 10 dígitos.',
            'phone.max'           => 'El teléfono no puede exceder los 15 dígitos.',
            'phone.regex'         => 'El formato del teléfono no es válido (ej: +521234567890).',
            'is_active.required'  => 'Debes indicar si el registro está activo o inactivo.',
            'is_active.boolean'   => 'El valor de activación debe ser verdadero o falso (1 o 0).',
        ];
    }
}
