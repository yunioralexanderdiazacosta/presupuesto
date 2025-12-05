<?php

namespace App\Http\Requests\FuelOutflows;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelOutflowRequest extends FormRequest
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
            'invoice_product_id' => 'nullable|exists:invoice_product,id',
            'credit_debit_note_item_id' => 'nullable|exists:credit_debit_note_items,id',
            'product_id' => 'required|exists:products,id',
            'project_id' => 'nullable|exists:projects,id',
            'operation_id' => 'nullable|exists:operations,id',
            'liters' => 'required|numeric|min:0.01',
            'counter_id' => 'nullable|exists:counters,id',
            'counter_value' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'observations' => 'nullable|string',
        ];
    }
}
