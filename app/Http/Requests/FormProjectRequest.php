<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'date'         => 'nullable|date',
            'observations' => 'nullable|string',
            'budget'       => 'nullable|integer|min:0',
            'operation_id' => 'nullable|exists:operations,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'         => 'nombre',
            'date'         => 'fecha',
            'budget'       => 'presupuesto',
            'operation_id' => 'operación',
        ];
    }
}
