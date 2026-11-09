<?php

namespace App\Http\Requests\ProductionLots;

use App\Models\ProductionLot;
use Illuminate\Foundation\Http\FormRequest;

/** Recepción de un lote en el packing (alta y edición). */
class FormProductionLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lot_number' => 'required|string|max:50',
            'reception_date' => 'required|date',
            'kg_received' => 'required|numeric|min:0.01',
            'observations' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /** @var ProductionLot|null $lot */
            $lot = $this->route('productionLot');
            $dispatch = $lot ? $lot->productionDispatch : $this->route('productionDispatch');
            if (!$dispatch) return;

            // El N° de lote lo asigna el packing: no se repite dentro del mismo packing y temporada
            $duplicated = ProductionLot::where('team_id', $dispatch->team_id)
                ->where('season_id', $dispatch->season_id)
                ->where('lot_number', $this->input('lot_number'))
                ->when($lot, fn($q) => $q->where('id', '!=', $lot->id))
                ->whereHas('productionDispatch', fn($q) => $q->where('packing_house_id', $dispatch->packing_house_id))
                ->exists();

            if ($duplicated) {
                $validator->errors()->add('lot_number', 'Ya existe un lote con este número en el mismo packing.');
            }

            // Un lote ya procesado no puede quedar con menos kg recepcionados que los que entraron a proceso
            if ($lot && $lot->status === 'processed'
                && (float) $this->input('kg_received', 0) < (float) $lot->kg_to_process - 0.001) {
                $validator->errors()->add(
                    'kg_received',
                    'El lote ya fue procesado con ' . number_format((float) $lot->kg_to_process, 2, ',', '.') . ' kg a proceso; los kg recepcionados no pueden ser menores.'
                );
            }
        });
    }
}
