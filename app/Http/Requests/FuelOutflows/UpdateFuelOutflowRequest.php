<?php

namespace App\Http\Requests\FuelOutflows;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFuelOutflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machinery_id' => 'required|exists:machineries,id',
            'operator_id' => 'required|exists:operators,id',
            'cost_center_id' => 'nullable|array',
            'cost_center_id.*' => 'exists:cost_centers,id',
            'invoice_product_id' => 'nullable|exists:invoice_products,id',
            'credit_debit_note_item_id' => 'nullable|exists:credit_debit_note_items,id',
            'product_id' => 'required|exists:products,id',
            'tank_id' => 'nullable|exists:fuel_tanks,id',
            'project_id' => 'nullable|exists:projects,id',
            'operation_id' => 'nullable|exists:operations,id',
            'liters' => 'required|numeric|min:0.01',
            'counter_id' => 'nullable|exists:counters,id',
            'counter_value' => 'nullable|numeric|min:0',
            'date' => 'required|date|after:2000-01-01|before:2100-01-01',
            'observations' => 'nullable|string',
        ];
    }
}
