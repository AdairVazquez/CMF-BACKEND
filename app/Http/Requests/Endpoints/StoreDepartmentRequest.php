<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
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
            'company_id'    => 'required|exists:companies,id',
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:255',
            'description'   => 'required|string|max:1000',
            'is_active'     => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            // Company & Branch
            'company_id.required' => 'Debes seleccionar una empresa.',
            'company_id.exists'   => 'La empresa seleccionada no es válida.',
            'branch_id.required'  => 'Debes seleccionar una sucursal.',
            'branch_id.exists'    => 'La sucursal seleccionada no es válida.',

            // Name & Code
            'name.required'       => 'El nombre es obligatorio.',
            'name.string'         => 'El nombre debe ser una cadena de texto.',
            'name.max'            => 'El nombre no puede tener más de 255 caracteres.',
            'code.max'            => 'El código no puede exceder los 255 caracteres.',

            // Description
            'description.required' => 'La descripción es necesaria para el registro.',
            'description.max'      => 'La descripción es demasiado larga (máximo 1000 caracteres).',

            // Status
            'is_active.required'   => 'Debes indicar si el registro está activo.',
            'is_active.boolean'    => 'El estado debe ser verdadero o falso.',
        ];
    }
}
