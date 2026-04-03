<?php

namespace App\Http\Requests\DailyAttendances;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'estimated_labor_type_id' => 'nullable|exists:labor_types,id',
            'estimated_cost_center_id' => 'nullable|exists:cost_centers,id',
            'attendances' => 'required|array|min:1',
            'attendances.*.employee_id' => 'required|exists:employees,id',
            'attendances.*.is_present' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'attendances.required' => 'Debe incluir al menos un trabajador.',
            'attendances.min' => 'Debe incluir al menos un trabajador.',
        ];
    }
}
