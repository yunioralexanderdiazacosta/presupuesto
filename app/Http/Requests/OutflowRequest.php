<?php

namespace App\Http\Requests;

use App\Models\Operation;
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
                'outflows.*.quantity' => 'required|numeric|min:0.01',
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
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
            'date' => 'required|date|after:2000-01-01|before:2100-01-01',
            'cost_center_ids' => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'level3_id' => 'required|exists:level3s,id',
        ];
    }
}

