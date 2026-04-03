<?php

namespace App\Http\Requests\BonusTypes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonusTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'default_amount' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del bono es obligatorio.',
            'name.max' => 'El nombre no puede exceder 150 caracteres.',
        ];
    }
}
