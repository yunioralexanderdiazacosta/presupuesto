<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
                    if (!self::validarRut($value)) {
                        $fail('El RUT ingresado no es válido.');
                    }
                },
                Rule::unique('employees')->where(fn ($query) =>
                    $query->where('team_id', auth()->user()->team_id)
                ),
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

    public static function validarRut(string $rut): bool
    {
        $rut = preg_replace('/[.\-]/', '', strtoupper(trim($rut)));
        if (strlen($rut) < 2) return false;

        $body = substr($rut, 0, -1);
        $dv = substr($rut, -1);

        if (!ctype_digit($body)) return false;

        $sum = 0;
        $mul = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += intval($body[$i]) * $mul;
            $mul = $mul === 7 ? 2 : $mul + 1;
        }

        $remainder = 11 - ($sum % 11);
        $expected = $remainder === 11 ? '0' : ($remainder === 10 ? 'K' : (string) $remainder);

        return $dv === $expected;
    }
}
