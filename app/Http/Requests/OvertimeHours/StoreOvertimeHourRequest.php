<?php

namespace App\Http\Requests\OvertimeHours;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id'      => ['required', 'exists:contracts,id'],
            'month_id'         => ['required', 'exists:months,id'],
            'labor_type_id'    => ['required', 'exists:labor_types,id'],
            'overtime_type_id' => ['required', 'exists:overtime_types,id'],
            'hours'            => ['required', 'numeric', 'min:0.01', 'max:24'],
            'cost_center_ids'  => ['required', 'array', 'min:1'],
            'cost_center_ids.*'=> ['exists:cost_centers,id'],
            'observations'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost_center_ids.required' => 'Debe seleccionar al menos un centro de costo.',
            'cost_center_ids.min'      => 'Debe seleccionar al menos un centro de costo.',
            'hours.max'                => 'No se pueden registrar más de 24 horas en un día.',
        ];
    }
}
