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
            'product_id' => 'required|exists:products,id',
            'liters' => 'required|numeric|min:0.01',
            'counter_id' => 'nullable|exists:counters,id',
            'counter_value' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'observations' => 'nullable|string',
        ];
    }
}
