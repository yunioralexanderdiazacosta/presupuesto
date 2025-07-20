<?php

namespace App\Http\Requests\Estimates;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstimateRequest extends FormRequest
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
        return [
            '*.estimate_status_id' => 'required|exists:estimate_status,id',
            '*.kilos_ha' => 'required|integer',
            '*.cost_center_id' => 'required|exists:cost_centers,id',
            '*.observations' => 'nullable|string', // No es obligatorio
        ];
    }
}
