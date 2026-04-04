<?php

namespace App\Http\Requests\DailyYields;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyYieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'payment_type' => 'required|in:trato,dia',
            'labor_type_id' => 'required|exists:labor_types,id',
            'labor_rate_id' => 'nullable|required_if:payment_type,trato|exists:labor_rates,id',
            'rate' => 'required|integer|min:0',
            'quantity' => 'required|numeric|min:0',
            'hours' => 'required|numeric|min:0.5|max:24',
            'bonus_type_id' => 'nullable|exists:bonus_types,id',
            'bonus_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'required|exists:cost_centers,id',
            'observations' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El trabajador es obligatorio.',
            'labor_type_id.required' => 'La labor es obligatoria.',
            'labor_rate_id.required_if' => 'El trato es obligatorio cuando el tipo es "a trato".',
            'rate.required' => 'La tarifa es obligatoria.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'hours.required' => 'Las horas son obligatorias.',
            'hours.min' => 'Mínimo 0.5 horas.',
            'cost_center_id.required' => 'El centro de costo es obligatorio.',
        ];
    }
}
