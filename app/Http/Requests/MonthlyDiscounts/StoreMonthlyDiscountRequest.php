<?php

namespace App\Http\Requests\MonthlyDiscounts;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id'              => 'required|exists:contracts,id',
            'monthly_discount_type_id' => 'required|exists:monthly_discount_types,id',
            'month_id'                 => 'required|exists:months,id',
            'amount'                   => 'required|integer|min:1',
            'observations'             => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'contract_id.required'              => 'Seleccione un contrato.',
            'monthly_discount_type_id.required' => 'Seleccione un tipo de descuento.',
            'month_id.required'                 => 'Seleccione un mes.',
            'amount.required'                   => 'El monto es obligatorio.',
            'amount.min'                        => 'El monto debe ser mayor a 0.',
        ];
    }
}
