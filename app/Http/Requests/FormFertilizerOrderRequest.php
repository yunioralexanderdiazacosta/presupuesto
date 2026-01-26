<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormFertilizerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'irrigation_pump_id' => 'nullable|exists:irrigation_pumps,id',
            'responsable' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.dosis_por_hectarea' => 'required|numeric|min:0.01',
            'products.*.unit_id' => 'nullable|exists:units,id',
            'irrigation_sectors' => 'required|array|min:1',
            'irrigation_sectors.*' => 'exists:irrigation_sectors,id',
            'cost_centers' => 'nullable|array',
            'cost_centers.*' => 'exists:cost_centers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria',
            'products.required' => 'Debe agregar al menos un producto',
            'products.min' => 'Debe agregar al menos un producto',
            'products.*.product_id.required' => 'Debe seleccionar un producto',
            'products.*.dosis_por_hectarea.required' => 'La dosis por hectárea es obligatoria',
            'products.*.dosis_por_hectarea.min' => 'La dosis debe ser mayor a 0',
            'irrigation_sectors.required' => 'Debe seleccionar al menos un sector de riego',
            'irrigation_sectors.min' => 'Debe seleccionar al menos un sector de riego',
        ];
    }
}
