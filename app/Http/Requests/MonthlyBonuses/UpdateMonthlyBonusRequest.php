<?php

namespace App\Http\Requests\MonthlyBonuses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyBonusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id'           => 'required|exists:contracts,id',
            'monthly_bonus_type_id' => 'required|exists:monthly_bonus_types,id',
            'month_id'              => 'required|exists:months,id',
            'cost_center_ids'        => 'required|array|min:1',
            'cost_center_ids.*'       => 'exists:cost_centers,id',
            'labor_type_id'         => 'required|exists:labor_types,id',
            'amount'                => 'required|integer|min:1',
            'observations'          => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'contract_id.required'           => 'Seleccione un contrato.',
            'monthly_bonus_type_id.required' => 'Seleccione un tipo de bono.',
            'month_id.required'              => 'Seleccione un mes.',
            'cost_center_ids.required'        => 'Seleccione al menos un centro de costo.',
            'cost_center_ids.min'             => 'Seleccione al menos un centro de costo.',
            'labor_type_id.required'          => 'Seleccione una labor.',
            'amount.required'                => 'El monto es obligatorio.',
            'amount.min'                     => 'El monto debe ser mayor a 0.',
        ];
    }
}
