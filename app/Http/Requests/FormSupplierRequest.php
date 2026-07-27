<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FormSupplierRequest extends FormRequest
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
        $teamId       = Auth::user()->team_id;
        $supplierId   = optional($this->route('supplier'))->id;

        return [
            'name'      => 'required|string|max:255',
            'rut'       => [
                'required',
                'string',
                'max:20',
                Rule::unique('suppliers', 'rut')
                    ->where(fn ($query) => $query->where('team_id', $teamId))
                    ->ignore($supplierId),
            ],
            'email'     => 'nullable|email|max:255',
            'contact'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'accounts'                    => 'nullable|array',
            'accounts.*.bank_id'          => 'required|exists:banks,id',
            'accounts.*.account_type_id'  => 'required|exists:account_types,id',
            'accounts.*.account_number'   => 'required|string|max:30',
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'El nombre del proveedor es obligatorio.',
            'rut.required'   => 'El RUT del proveedor es obligatorio.',
            'rut.unique'     => 'Ya existe un proveedor registrado con este RUT.',
            'email.email'    => 'El email ingresado no es válido.',
            'accounts.*.bank_id.required'         => 'El banco es obligatorio en cada cuenta.',
            'accounts.*.account_type_id.required' => 'El tipo de cuenta es obligatorio en cada cuenta.',
            'accounts.*.account_number.required'  => 'El número de cuenta es obligatorio en cada cuenta.',
        ];
    }
}
