<?php

namespace App\Http\Requests\ProductionDispatches;

use Illuminate\Foundation\Http\FormRequest;

class ProcessProductionDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_date' => 'required|date',
            'kg_received' => 'required|numeric|min:0',
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
            $dispatch = $this->route('productionDispatch');
            if (!$dispatch) return;

            $kgLimit = (float) $dispatch->kg_dispatched;
            $items = $this->input('items', []);

            $totalsByType = [];
            foreach ($items as $item) {
                $type = $item['classification_type'] ?? '';
                if ($type) {
                    $totalsByType[$type] = ($totalsByType[$type] ?? 0) + (float) ($item['kg'] ?? 0);
                }
            }

            $labels = ['caliber' => 'Calibre', 'color' => 'Color', 'quality' => 'Calidad'];
            foreach ($totalsByType as $type => $total) {
                if ($total > $kgLimit) {
                    $label = $labels[$type] ?? $type;
                    $validator->errors()->add(
                        'items',
                        "Los kg de {$label} (" . number_format($total, 2, ',', '.') . ") superan los kg despachados (" . number_format($kgLimit, 2, ',', '.') . ")."
                    );
                }
            }

            // Validar que Export+Nacional+Industrial+Descarte <= Kilos a Proceso
            $kgReceived = (float) $this->input('kg_received', 0);
            $kgBreakdown = (float) $this->input('kg_exported', 0)
                + (float) $this->input('kg_national', 0)
                + (float) $this->input('kg_industrial', 0)
                + (float) $this->input('kg_waste', 0);

            if ($kgReceived > 0 && $kgBreakdown > $kgReceived) {
                $validator->errors()->add(
                    'kg_exported',
                    'La suma de Exportación + Nacional + Industrial + Descarte (' . number_format($kgBreakdown, 2, ',', '.') . ') supera los Kilos a Proceso (' . number_format($kgReceived, 2, ',', '.') . ').'
                );
            }
        });
    }
}
