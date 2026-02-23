<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name'      => 'required|string|max:255',
            'rut'       => 'required|string|max:20',
            'email'     => 'nullable|email|max:255',
            'contact'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:50'
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
            'email.email'    => 'El email ingresado no es válido.',
        ];
    }
}
