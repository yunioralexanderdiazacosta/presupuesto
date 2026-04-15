<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'company_reason_id' => 'required|exists:company_reasons,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'contract_date' => 'required|date',
            'contract_type' => 'required|string|in:Faena,Plazo Fijo,Indefinido',
            'position' => 'nullable|string|max:150',
            'labor' => 'nullable|string|max:150',
            'base_salary' => 'required|numeric|min:0',
            'net_salary' => 'required|numeric|min:0',
            'afp_id' => 'nullable|exists:afps,id',
            'health_plan_id' => 'nullable|exists:health_plans,id',
            'city_id' => 'nullable|exists:cities,id',
            'parcel_id' => 'nullable|exists:parcels,id',
            'marital_status' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:150',
            'end_date' => 'nullable|date|after_or_equal:contract_date',
            'is_active' => 'boolean',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_id' => 'nullable|exists:banks,id',
            'account_type_id' => 'nullable|exists:account_types,id',
            'account_number' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Debe seleccionar un colaborador.',
            'company_reason_id.required' => 'Debe seleccionar una empresa.',
            'contract_date.required' => 'La fecha de contrato es obligatoria.',
            'contract_type.required' => 'El tipo de contrato es obligatorio.',
            'base_salary.required' => 'El sueldo base es obligatorio.',
            'net_salary.required' => 'El sueldo líquido es obligatorio.',
            'end_date.after_or_equal' => 'La fecha de término debe ser posterior a la fecha de contrato.',
        ];
    }
}
