<?php

namespace App\Http\Requests\ProductionProcesses;

use App\Models\ProductionLot;
use App\Models\ProductionProcess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FormProductionProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_number' => 'required|string|max:50',
            'process_date' => 'required|date',
            'kg_exported' => 'nullable|numeric|min:0',
            'kg_national' => 'nullable|numeric|min:0',
            'kg_industrial' => 'nullable|numeric|min:0',
            'kg_waste' => 'nullable|numeric|min:0',
            'lots' => 'required|array|min:1',
            'lots.*.id' => 'required|integer|distinct',
            'lots.*.kg' => 'required|numeric|gt:0',
            'items' => 'nullable|array',
            'items.*.classification_type' => 'required_with:items|string',
            'items.*.classification_value' => 'required_with:items|string',
            'items.*.kg' => 'required_with:items|numeric|min:0',
            'items.*.boxes' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'lots.required' => 'Debe incluir al menos un lote en el proceso.',
            'lots.min' => 'Debe incluir al menos un lote en el proceso.',
            'lots.*.kg.gt' => 'Los kg de cada lote deben ser mayores a 0.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $user = Auth::user();
            $seasonId = session('season_id');
            $process = $this->route('productionProcess');

            $duplicated = ProductionProcess::where('team_id', $user->team_id)
                ->where('season_id', $process?->season_id ?? $seasonId)
                ->where('process_number', $this->input('process_number'))
                ->when($process, fn($q) => $q->where('id', '!=', $process->id))
                ->exists();
            if ($duplicated) {
                $validator->errors()->add('process_number', 'Ya existe un proceso con ese número en la temporada.');
            }

            $requested = collect($this->input('lots'))->pluck('kg', 'id');
            $lots = ProductionLot::with('productionDispatch.costCenters.costCenterVariety')
                ->whereIn('id', $requested->keys())
                ->where('team_id', $user->team_id)
                ->where('season_id', $process?->season_id ?? $seasonId)
                ->get()
                ->keyBy('id');

            if ($lots->count() !== $requested->count()) {
                $validator->errors()->add('lots', 'Alguno de los lotes seleccionados no es válido.');
                return;
            }

            // Lo ya aportado por este mismo proceso se libera al editarlo
            $ownKg = $process
                ? $process->allocations()->pluck('kg_to_process', 'production_lot_id')
                : collect();

            foreach ($lots as $lot) {
                $available = (float) $lot->kg_received - (float) $lot->kg_to_process + (float) ($ownKg[$lot->id] ?? 0);
                if ((float) $requested[$lot->id] - $available > 0.001) {
                    $validator->errors()->add(
                        'lots',
                        'Los kg del lote ' . $lot->lot_number . ' (' . number_format((float) $requested[$lot->id], 2, ',', '.') . ') superan su saldo disponible (' . number_format(max($available, 0), 2, ',', '.') . ').'
                    );
                }
            }

            $fruitIds = $lots
                ->map(fn($lot) => $lot->productionDispatch->costCenters->first()?->costCenterVariety?->fruit_id)
                ->unique();
            if ($fruitIds->count() > 1) {
                $validator->errors()->add('lots', 'Los lotes de un proceso deben ser del mismo frutal.');
            }

            $kgToProcess = (float) $requested->sum();

            $kgBreakdown = (float) $this->input('kg_exported', 0)
                + (float) $this->input('kg_national', 0)
                + (float) $this->input('kg_industrial', 0)
                + (float) $this->input('kg_waste', 0);

            if ($kgBreakdown - $kgToProcess > 0.001) {
                $validator->errors()->add(
                    'kg_exported',
                    'La suma de Exportación + Comercial + Industrial + Descarte (' . number_format($kgBreakdown, 2, ',', '.') . ') supera los Kilos a Proceso (' . number_format($kgToProcess, 2, ',', '.') . ').'
                );
            }

            // Las clasificaciones se desglosan sobre los kg de exportación
            $kgLimit = (float) $this->input('kg_exported', 0);
            $totalsByType = [];
            foreach ($this->input('items', []) as $item) {
                $type = $item['classification_type'] ?? '';
                if ($type) {
                    $totalsByType[$type] = ($totalsByType[$type] ?? 0) + (float) ($item['kg'] ?? 0);
                }
            }

            $labels = ['caliber' => 'Calibre', 'color' => 'Color', 'quality' => 'Calidad'];
            foreach ($totalsByType as $type => $total) {
                if ($total - $kgLimit > 0.001) {
                    $label = $labels[$type] ?? $type;
                    $validator->errors()->add(
                        'items',
                        "Los kg de {$label} (" . number_format($total, 2, ',', '.') . ") superan los kg de exportación (" . number_format($kgLimit, 2, ',', '.') . ")."
                    );
                }
            }
        });
    }
}
