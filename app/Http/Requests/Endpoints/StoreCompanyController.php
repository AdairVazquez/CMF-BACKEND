<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyController extends FormRequest
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
            'name'       => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'tax_id'     => 'nullable|string|max:255',
            'email'      => 'required|email|unique:companies,email',
            'phone' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],
            'address'    => 'required|string|max:255',
            'logo' => [
                'nullable',
                'file',
                'mimes:svg,xml',
                'max:2048', // 2MB está perfecto
            ],
            'plan'       => 'required|string|max:255',
            'status'     => [
                'required',
                Rule::in(['activo', 'suspendido', 'inactivo', 'prueba'])
            ],
            'timezone' => 'required|timezone:all', // Valida que sea una zona horaria real 
            'modules'   => [
                'nullable',
                'array' // Validamos que el cliente envíe un arreglo []
            ],
            'modules.*' => [
                'string',
                'max:50' // Validamos que cada nombre de módulo sea texto y no sea exageradamente largo
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre de la empresa es obligatorio.',
            'legal_name.required' => 'La razón social es obligatoria.',
            'email.required'      => 'El correo electrónico es indispensable.',
            'email.email'         => 'Ingresa un formato de correo válido.',
            'email.unique'        => 'Este correo ya pertenece a otra empresa registrada.',
            'phone.required'      => 'El teléfono es obligatorio.',
            'phone.regex'         => 'El formato de teléfono no es válido (ej: +521234567890).',
            'phone.min'           => 'El teléfono debe tener al menos 10 dígitos.',
            'logo.mimes'          => 'El logo debe ser un archivo de tipo: svg, xml.',
            'logo.max'            => 'El logo es muy pesado, máximo 2MB.',
            'status.required'     => 'Debes seleccionar un estatus para la empresa.',
            'status.in'           => 'El estatus seleccionado no es válido.',
            'timezone.required'   => 'La zona horaria es obligatoria para los reportes.',
            'timezone.timezone'   => 'La zona horaria seleccionada no existe.',
            'modules.array'       => 'Los módulos deben enviarse como una lista.',
            'modules.*.string'    => 'El nombre del módulo debe ser texto.',
        ];
    }
}
