<?php

namespace App\Http\Requests\ProductionDispatches;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_center_variety_id' => 'required|exists:cost_center_varieties,id',
            'exporter_id' => 'required|exists:exporters,id',
            'packing_house_id' => 'required|exists:packing_houses,id',
            'dispatch_date' => 'required|date',
            'guide_number' => 'required|string|max:50',
            'lot_number' => 'nullable|string|max:50',
            'kg_dispatched' => 'required|numeric|min:0.01',
            'bin_type_id' => 'nullable|exists:bin_types,id',
            'bins_quantity' => 'nullable|integer|min:0',
            'box_type_id' => 'nullable|exists:box_types,id',
            'boxes_quantity' => 'nullable|integer|min:0',
            'carrier_id' => 'nullable|exists:carriers,id',
            'driver' => 'nullable|string|max:100',
            'license_plate' => 'nullable|string|max:20',
            'observations' => 'nullable|string',
        ];
    }
}
