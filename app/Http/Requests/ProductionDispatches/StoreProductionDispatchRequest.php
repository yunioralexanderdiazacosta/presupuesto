<?php

namespace App\Http\Requests\ProductionDispatches;

use App\Models\CostCenterVariety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductionDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = Auth::user()->team_id;
        $seasonId = session('season_id');

        return [
            'cost_centers' => 'required|array|min:1',
            'cost_centers.*.cost_center_variety_id' => [
                'required',
                'distinct',
                Rule::exists('cost_center_varieties', 'id')->where('team_id', $teamId)->where('season_id', $seasonId),
            ],
            'cost_centers.*.kg_dispatched' => 'required|numeric|min:0.01',
            'exporter_id' => 'required|exists:exporters,id',
            'packing_house_id' => 'required|exists:packing_houses,id',
            'dispatch_date' => 'required|date',
            'guide_number' => 'required|string|max:50',
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

    public function messages(): array
    {
        return [
            'cost_centers.required' => 'Seleccione al menos un cuartel.',
            'cost_centers.*.cost_center_variety_id.distinct' => 'Un cuartel no puede repetirse en la misma guía.',
            'cost_centers.*.kg_dispatched.required' => 'Indique los kg de cada cuartel.',
            'cost_centers.*.kg_dispatched.min' => 'Los kg de cada cuartel deben ser mayores a 0.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            // Las clasificaciones del proceso dependen del frutal: todos los cuarteles de la guía deben ser del mismo
            $ids = collect($this->input('cost_centers', []))->pluck('cost_center_variety_id');
            if (CostCenterVariety::whereIn('id', $ids)->distinct()->pluck('fruit_id')->count() > 1) {
                $validator->errors()->add('cost_centers', 'Todos los cuarteles de una guía deben ser del mismo frutal.');
            }
        });
    }
}
