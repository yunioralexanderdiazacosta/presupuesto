<?php

namespace App\Http\Requests;

use App\Models\Operation;
use App\Support\SpecificTax;
use Illuminate\Foundation\Http\FormRequest;

class OutflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida que exista investment_id cuando la operación indicada es de tipo "Inversión".
     */
    protected function investmentRequiredWhenInversionRule(callable $getOperationId): \Closure
    {
        return function ($attribute, $value, $fail) use ($getOperationId) {
            $operationId = $getOperationId($attribute);
            if (!$operationId || $value) {
                return;
            }
            $isInversion = Operation::whereKey($operationId)->where('name', 'like', '%invers%')->exists();
            if ($isInversion) {
                $fail('Debe seleccionar una inversión cuando la operación es Inversión.');
            }
        };
    }

    /**
     * Cantidad de la salida: distinta de cero. Solo las líneas de Impuesto Específico admiten
     * cantidad negativa (reverso de consumo de una línea negativa de la factura).
     */
    protected function quantityRule(callable $getInvoiceProductId): array
    {
        return [
            'required',
            'numeric',
            function ($attribute, $value, $fail) use ($getInvoiceProductId) {
                $qty = (float) $value;
                if ($qty == 0.0) {
                    $fail('La cantidad debe ser distinta de cero.');
                } elseif ($qty > 0 && $qty < 0.01) {
                    $fail('La cantidad mínima es 0,01.');
                } elseif ($qty < 0 && !SpecificTax::isInvoiceProduct($getInvoiceProductId($attribute))) {
                    $fail('Solo el Impuesto Específico admite cantidad negativa.');
                }
            },
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Si la petición contiene 'outflows', es creación masiva
        if ($this->has('outflows')) {
            return [
                'outflows' => 'required|array|min:1',
                'outflows.*.project_id' => 'nullable|exists:projects,id',
                'outflows.*.operation_id' => 'required|exists:operations,id',
                'outflows.*.investment_id' => [
                    'nullable',
                    'exists:investments,id',
                    $this->investmentRequiredWhenInversionRule(function ($attribute) {
                        preg_match('/outflows\.(\d+)\.investment_id/', $attribute, $matches);
                        return $matches[1] ?? null
                            ? $this->input('outflows.' . $matches[1] . '.operation_id')
                            : null;
                    }),
                ],
                'outflows.*.machinery_id' => 'nullable|exists:machineries,id',
                'outflows.*.quantity' => $this->quantityRule(function ($attribute) {
                    preg_match('/outflows\.(\d+)\.quantity/', $attribute, $matches);
                    return $this->input('outflows.' . ($matches[1] ?? 0) . '.invoice_product_id');
                }),
                'outflows.*.notes' => 'nullable|string|max:255',
                'outflows.*.date' => 'required|date|after:2000-01-01|before:2100-01-01',
                'outflows.*.cost_center_ids' => 'nullable|array',
                'outflows.*.cost_center_ids.*' => 'exists:cost_centers,id',
                'outflows.*.level3_id' => 'required|exists:level3s,id',
            ];
        }
        // Si no, es edición individual
        return [
            'project_id' => 'nullable|exists:projects,id',
            'operation_id' => 'required|exists:operations,id',
            'investment_id' => [
                'nullable',
                'exists:investments,id',
                $this->investmentRequiredWhenInversionRule(fn () => $this->input('operation_id')),
            ],
            'machinery_id' => 'nullable|exists:machineries,id',
            'quantity' => $this->quantityRule(fn () => $this->route('outflow')?->invoice_product_id),
            'notes' => 'nullable|string|max:255',
            'date' => 'required|date|after:2000-01-01|before:2100-01-01',
            'cost_center_ids' => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'level3_id' => 'required|exists:level3s,id',
        ];
    }
}

