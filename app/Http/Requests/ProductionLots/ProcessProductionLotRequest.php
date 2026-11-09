<?php

namespace App\Http\Requests\ProductionLots;

use Illuminate\Foundation\Http\FormRequest;

class ProcessProductionLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_date' => 'required|date',
            'kg_to_process' => 'required|numeric|min:0',
            'kg_exported' => 'nullable|numeric|min:0',
            'kg_national' => 'nullable|numeric|min:0',
            'kg_industrial' => 'nullable|numeric|min:0',
            'kg_waste' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.classification_type' => 'required_with:items|string',
            'items.*.classification_value' => 'required_with:items|string',
            'items.*.kg' => 'required_with:items|numeric|min:0',
            'items.*.boxes' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lot = $this->route('productionLot');
            if (!$lot) return;

            $kgToProcess = (float) $this->input('kg_to_process', 0);

            // Lo que entra a proceso no puede superar lo recepcionado en el packing
            if ($kgToProcess - (float) $lot->kg_received > 0.001) {
                $validator->errors()->add(
                    'kg_to_process',
                    'Los kg a proceso (' . number_format($kgToProcess, 2, ',', '.') . ') superan los kg recepcionados del lote (' . number_format((float) $lot->kg_received, 2, ',', '.') . ').'
                );
            }

            $kgBreakdown = (float) $this->input('kg_exported', 0)
                + (float) $this->input('kg_national', 0)
                + (float) $this->input('kg_industrial', 0)
                + (float) $this->input('kg_waste', 0);

            if ($kgToProcess > 0 && $kgBreakdown - $kgToProcess > 0.001) {
                $validator->errors()->add(
                    'kg_exported',
                    'La suma de Exportación + Nacional + Industrial + Descarte (' . number_format($kgBreakdown, 2, ',', '.') . ') supera los Kilos a Proceso (' . number_format($kgToProcess, 2, ',', '.') . ').'
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
