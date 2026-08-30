<?php

namespace App\Http\Requests\Agrochemicals;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgrochemicalRequest extends FormRequest
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
        $inversionId = \App\Models\Operation::whereRaw('LOWER(name) LIKE ?', ['%inversion%'])->value('id');
        return [
            'cc' => 'required',
            'subfamily_id' => 'required',
            'operation_id' => 'required|exists:operations,id',
            'investment_id' => $inversionId ? "nullable|exists:investments,id|required_if:operation_id,{$inversionId}" : 'nullable|exists:investments,id',
            'products' => 'required|array',
            'products.*.product_name' => 'required',
            'products.*.unit_id' => 'required',
            'products.*.unit_id_price' => 'required',
            'products.*.dose' => 'required',
            'products.*.mojamiento' => 'required',
            'products.*.price' => 'required',
            'products.*.months' => 'required',
            'products.*.dose_type_id' => 'required'
        ];
    }
}
