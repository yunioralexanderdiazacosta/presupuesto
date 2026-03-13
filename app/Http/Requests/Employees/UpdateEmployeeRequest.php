<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Employees\StoreEmployeeRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'second_name' => 'nullable|string|max:100',
            'paternal_surname' => 'required|string|max:100',
            'maternal_surname' => 'nullable|string|max:100',
            'rut' => [
                'required',
                'string',
                'max:12',
                function ($attribute, $value, $fail) {
                    if (!StoreEmployeeRequest::validarRut($value)) {
                        $fail('El RUT ingresado no es válido.');
                    }
                },
                Rule::unique('employees')->where(fn ($query) =>
                    $query->where('team_id', auth()->user()->team_id)
                )->ignore($this->route('employee')),
            ],
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:60',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'rut.unique' => 'Este RUT ya está registrado en el sistema.',
            'first_name.required' => 'El nombre es obligatorio.',
            'paternal_surname.required' => 'El apellido paterno es obligatorio.',
        ];
    }
}
