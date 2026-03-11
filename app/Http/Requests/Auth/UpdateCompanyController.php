<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyController extends FormRequest
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
        // Obtenemos el ID de la empresa desde la ruta
        $companyId = $this->route('company')->id;

        return [
            'name'       => 'sometimes|required|string|max:255',
            'legal_name' => 'sometimes|required|string|max:255',
            'tax_id'     => 'sometimes|required|string|max:255',
            // 'ignore' permite que el registro actual mantenga su email
            'email'      => "sometimes|required|email|unique:companies,email,{$companyId}",
            'phone'      => 'sometimes|required|string|min:10|max:15',
            'address'    => 'sometimes|required|string|max:255',
            'logo'       => 'nullable|file|mimes:svg,xml|max:2048',
            'plan'       => 'sometimes|required|string|max:255',
            'status'     => ['sometimes', 'required', \Illuminate\Validation\Rule::in(['activo', 'suspendido', 'inactivo', 'prueba'])],
            'timezone'   => 'sometimes|required|timezone:all',
            'modules'    => 'nullable|array',
        ];
    }
}
