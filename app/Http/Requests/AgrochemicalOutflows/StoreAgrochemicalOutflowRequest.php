<?php

namespace App\Http\Requests\AgrochemicalOutflows;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgrochemicalOutflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'application_order_id' => 'required|exists:application_orders,id',
            'maquinadas' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'observations' => 'nullable|string|max:500',
            
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            
            'products.*.lines' => 'required|array|min:1',
            'products.*.lines.*.invoice_product_id' => 'required|exists:invoice_products,id',
            'products.*.lines.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'application_order_id.required' => 'Debe seleccionar una orden de aplicación.',
            'maquinadas.required' => 'Las maquinadas son obligatorias.',
            'maquinadas.min' => 'Las maquinadas deben ser mayores a 0.',
            'date.required' => 'La fecha es obligatoria.',
            'products.required' => 'Debe agregar al menos un producto.',
            'products.*.real_quantity.required' => 'La cantidad real es obligatoria.',
            'products.*.real_quantity.min' => 'La cantidad debe ser mayor a 0.',
            'products.*.lines.required' => 'Debe agregar al menos una línea de factura.',
            'products.*.lines.*.invoice_product_id.required' => 'Debe seleccionar una factura.',
            'products.*.lines.*.quantity.required' => 'La cantidad es obligatoria.',
            'products.*.lines.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
