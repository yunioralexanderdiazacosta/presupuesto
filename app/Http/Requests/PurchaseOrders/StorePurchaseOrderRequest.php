<?php

namespace App\Http\Requests\PurchaseOrders;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'cost_center_ids' => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'assigned_to.exists' => 'El aprobador seleccionado no existe.',
            'cost_center_ids.array' => 'Los centros de costo deben ser un arreglo.',
            'cost_center_ids.*.exists' => 'Uno de los centros de costo seleccionados no existe.',
            'order_date.required' => 'La fecha de orden es obligatoria.',
            'order_date.date' => 'La fecha de orden no es válida.',
            'delivery_date.date' => 'La fecha de entrega no es válida.',
            'delivery_date.after_or_equal' => 'La fecha de entrega debe ser igual o posterior a la fecha de orden.',
            'payment_terms.max' => 'Las condiciones de pago no pueden exceder 255 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'items.required' => 'Debe agregar al menos un producto.',
            'items.min' => 'Debe agregar al menos un producto.',
            'items.*.product_id.required' => 'El producto es obligatorio en cada línea.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.numeric' => 'La cantidad debe ser un número.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_id.required' => 'La unidad es obligatoria.',
            'items.*.unit_id.exists' => 'Una de las unidades seleccionadas no existe.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
            'items.*.unit_price.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'items.*.notes.max' => 'Las notas de línea no pueden exceder 500 caracteres.',
        ];
    }
}
