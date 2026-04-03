<?php

namespace App\Http\Requests\LaborTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaborTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'level3_id' => 'nullable|exists:level3s,id',
            'unit_id' => 'nullable|exists:units,id',
            'default_rate' => 'nullable|integer|min:0',
            'default_bonus' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la labor es obligatorio.',
            'name.max' => 'El nombre no puede exceder 150 caracteres.',
        ];
    }
}
