<?php

namespace App\Http\Requests\ApplicationOrders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationOrderRequest extends FormRequest
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
            'date' => 'required|date',
            'start_date' => 'nullable|date',
            'volume' => 'nullable|integer|min:0',
            'mojamiento' => 'required|numeric|min:0',
            'recomendado' => 'required|string|max:255',
            'aplicadores' => 'required|string',
            'status' => 'required|in:pendiente,en_proceso,completada,cancelada',
            'responsable' => 'required|string|max:255',
            'observations' => 'nullable|string',
            'phenological_stage_id' => 'nullable|exists:phenological_stages,id',
            
            // Productos (array de productos)
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.tipo_dosis' => 'required|in:por_hectarea,por_100_litros',
            'products.*.dosis_por_100' => 'nullable|numeric|min:0',
            'products.*.dosis_por_hectarea' => 'nullable|numeric|min:0',
            'products.*.carencia' => 'required|integer|min:0',
            'products.*.reingreso' => 'required|integer|min:0',
            
            // Centros de costo (array)
            'cost_centers' => 'required|array|min:1',
            'cost_centers.*.cost_center_id' => 'required|exists:cost_centers,id',
            'cost_centers.*.surface' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'mojamiento.required' => 'El mojamiento es obligatorio.',
            'mojamiento.numeric' => 'El mojamiento debe ser un número.',
            'recomendado.required' => 'El nombre de quien recomienda es obligatorio.',
            'aplicadores.required' => 'Los nombres de aplicadores son obligatorios.',
            'status.required' => 'El estado es obligatorio.',
            'responsable.required' => 'El responsable es obligatorio.',
            'products.required' => 'Debe agregar al menos un producto.',
            'products.min' => 'Debe agregar al menos un producto.',
            'cost_centers.required' => 'Debe seleccionar al menos un centro de costo.',
            'cost_centers.min' => 'Debe seleccionar al menos un centro de costo.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Validar lógica de dosis mutuamente excluyentes
        if ($this->has('products')) {
            $products = $this->input('products', []);
            
            foreach ($products as $key => $product) {
                if (isset($product['tipo_dosis'])) {
                    if ($product['tipo_dosis'] === 'por_hectarea') {
                        // Si es por hectárea, dosis_por_100 debe ser null
                        $products[$key]['dosis_por_100'] = null;
                    } elseif ($product['tipo_dosis'] === 'por_100_litros') {
                        // Si es por 100 litros, dosis_por_hectarea debe ser null
                        $products[$key]['dosis_por_hectarea'] = null;
                    }
                }
            }
            
            $this->merge(['products' => $products]);
        }
    }
}
