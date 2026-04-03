<?php

namespace App\Http\Requests\DailyYields;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyYieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labor_type_id' => 'required|exists:labor_types,id',
            'rate' => 'required|integer|min:0',
            'quantity' => 'required|numeric|min:0',
            'hours' => 'required|numeric|min:0.5|max:24',
            'bonus_type_id' => 'nullable|exists:bonus_types,id',
            'bonus_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'observations' => 'nullable|string|max:500',
        ];
    }
}
