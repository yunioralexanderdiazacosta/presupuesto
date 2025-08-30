<?php

namespace App\Http\Requests;

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
                'outflows.*.operation_id' => 'nullable|exists:operations,id',
                'outflows.*.machinery_id' => 'nullable|exists:machineries,id',
                'outflows.*.quantity' => 'required|numeric|min:0.01',
                'outflows.*.notes' => 'nullable|string|max:255',
                'outflows.*.cost_center_ids' => 'nullable|array',
                'outflows.*.cost_center_ids.*' => 'exists:cost_centers,id',
            ];
        }
        // Si no, es edición individual
        return [
            'project_id' => 'nullable|exists:projects,id',
            'operation_id' => 'nullable|exists:operations,id',
            'machinery_id' => 'nullable|exists:machineries,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
            'cost_center_ids' => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
        ];
    }
}
