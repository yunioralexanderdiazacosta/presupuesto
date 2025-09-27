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
            'cost_center_id' => 'required|exists:cost_centers,id',
            'fuel_type' => 'required|string|max:50',
            'liters' => 'required|numeric|min:0.01',
            'horometer' => 'nullable|numeric|min:0',
            'odometer' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'observations' => 'nullable|string',
        ];
    }
}
