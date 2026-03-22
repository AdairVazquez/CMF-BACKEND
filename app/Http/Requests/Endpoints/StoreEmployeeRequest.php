<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            'branch_id'     => 'required|exists:branches.id',
            'department_id' => 'required|exists:depatments.id',
            'shift_id'      => 'required|exists:shifts.id',
            'employee_codee' => 'required|string|max:255',
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'      => 'required|email|unique:,email',
            'phone' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],
            'employee_type'     => [
                'required',
                Rule::in(['base', 'confianza'])
            ],
            'status'     => [
                'required',
                Rule::in(['activo', 'inactivo', 'baja', 'suspendido'])
            ],
            'hire_date'    => 'required|date',
            'termination_date'    => 'required|date',
            'position' => 'required|string|max:255',
            'hierarchy_level' => 'required',
            'metadata' => 'required|string|max:1000',

        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists'    => 'La empresa seleccionada no es válida.',
            'branch_id.exists'     => 'La sucursal seleccionada no existe.',
            'department_id.exists' => 'El departamento seleccionado no es válido.',
            'shift_id.exists'      => 'El turno seleccionado no existe.',
            'employee_codee.required' => 'El código de empleado es obligatorio.',
            'email.unique'         => 'Este correo electrónico ya está registrado en el sistema.',
            'email.email'          => 'Debes ingresar un formato de correo válido.',
            'phone.regex'          => 'El formato del teléfono no es válido (ej: +521234567890).',
            'employee_type.in'     => 'El tipo de empleado debe ser: base o confianza.',
            'status.in'            => 'El estado seleccionado no es una opción válida.',
            'hire_date.date'       => 'La fecha de contratación debe ser una fecha válida.',
            'termination_date.date' => 'La fecha de baja debe ser una fecha válida.',
            'hierarchy_level.required' => 'Debes asignar un nivel jerárquico.',

            // Mensaje genérico para todos los 'required'
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
