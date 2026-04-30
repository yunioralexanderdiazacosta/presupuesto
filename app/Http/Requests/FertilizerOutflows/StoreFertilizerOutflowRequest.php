<?php

namespace App\Http\Requests\FertilizerOutflows;

use Illuminate\Foundation\Http\FormRequest;

class StoreFertilizerOutflowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fertilizer_order_id' => 'required|exists:fertilizer_orders,id',
            'date' => 'required|date',
            'observations' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.real_quantity' => 'required|numeric|min:0.01',
            'products.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'products.*.lines' => 'required|array|min:1',
            'products.*.lines.*.invoice_product_id' => 'required|exists:invoice_products,id',
            'products.*.lines.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function messages()
    {
        return [
            'fertilizer_order_id.required' => 'Debe seleccionar una orden de fertilizante',
            'date.required' => 'La fecha es obligatoria',
            'products.required' => 'Debe agregar al menos un producto',
            'products.*.product_id.required' => 'El producto es obligatorio',
            'products.*.real_quantity.required' => 'La cantidad real es obligatoria',
            'products.*.cost_center_id.required' => 'El centro de costo es obligatorio',
            'products.*.lines.*.quantity.required' => 'La cantidad es obligatoria',
        ];
    }
}
