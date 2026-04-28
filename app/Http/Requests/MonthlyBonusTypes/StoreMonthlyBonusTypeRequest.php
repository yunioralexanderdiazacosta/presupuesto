<?php

namespace App\Http\Requests\MonthlyBonusTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyBonusTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:150',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede exceder 150 caracteres.',
        ];
    }
}
