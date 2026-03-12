<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
            'company_id'  => 'sometimes|required|exists:companies,id',
            'name'        => 'sometimes|required|string|max:255',
            'code'        => 'nullable|string|max:255',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:255',
            'state'       => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'phone'       => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^\+?[1-9]\d{1,14}$/'
            ],
            'is_active'   => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists' => 'La empresa matriz seleccionada no es válida.',
            'phone.regex'       => 'El formato del teléfono debe ser internacional (ej: +521234567890).',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
