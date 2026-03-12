<?php

namespace App\Http\Requests\Endpoints;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemId = $this->route('item');

        return [
            'company_id'    => 'required|exists:companies,id',
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:255|unique:your_table_name,code,' . $itemId,
            'description'   => 'required|string|max:1000',
            'is_active'     => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            // Company & Branch
            'company_id.required' => 'La empresa es obligatoria para actualizar el registro.',
            'company_id.exists'   => 'La empresa seleccionada no existe en nuestra base de datos.',
            'branch_id.required'  => 'Debes especificar la sucursal.',
            'branch_id.exists'    => 'La sucursal seleccionada no es válida.',

            // Name & Code
            'name.required'       => 'El nombre no puede quedar vacío.',
            'name.string'         => 'El nombre debe ser un texto válido.',
            'name.max'            => 'El nombre es muy largo (máximo 255 caracteres).',
            'code.string'         => 'El formato del código no es válido.',
            'code.max'            => 'El código no debe exceder los 255 caracteres.',
            'code.unique'         => 'Este código ya está siendo usado por otro registro.', // Importante en Update

            // Description
            'description.required' => 'Por favor, incluye una descripción.',
            'description.max'      => 'La descripción no puede pasar de 1000 caracteres.',

            // Status
            'is_active.required'   => 'El estado de activación es obligatorio.',
            'is_active.boolean'    => 'El valor de estado debe ser booleano (Activo/Inactivo).',
        ];
    }
}
