<?php

namespace App\Http\Requests\Endpoints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        return [
            'company_id'       => 'required|exists:companies,id',
            'branch_id'        => 'required|exists:branches,id',
            'department_id'    => 'required|exists:departments,id',
            'shift_id'         => 'required|exists:shifts,id',
            'employee_codee'   => 'required|string|max:255|unique:employees,employee_codee',
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:employees,email',
            'phone'            => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],
            'employee_type'    => [
                'required',
                Rule::in(['base', 'confianza'])
            ],
            'status'           => [
                'required',
                Rule::in(['activo', 'inactivo', 'baja', 'suspendido'])
            ],
            'hire_date'        => 'required|date',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'position'         => 'required|string|max:255',
            'hierarchy_level'  => 'required|integer',
            'metadata'         => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            // Requeridos genéricos
            'required' => 'El campo :attribute es obligatorio.',
            
            // Validaciones específicas
            'email.unique'         => 'Este correo electrónico ya está registrado.',
            'email.email'          => 'Ingresa un formato de correo válido.',
            'employee_codee.unique'=> 'Este código de empleado ya está en uso.',
            'phone.regex'          => 'El formato del teléfono no es válido (ej: +52...).',
            'exists'               => 'El :attribute seleccionado no es válido.',
            'date'                 => 'El campo :attribute debe ser una fecha válida.',
            'after_or_equal'       => 'La fecha de término no puede ser anterior a la de contratación.',
            'employee_type.in'     => 'El tipo de empleado debe ser: base o confianza.',
            'status.in'            => 'El estado seleccionado no es válido.',
        ];
    }
}
