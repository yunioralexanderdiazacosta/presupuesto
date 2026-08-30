<?php

namespace App\Http\Requests\Fields;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldRequest extends FormRequest
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
           
            'subfamily_id' => 'required',
            'operation_id' => 'required|exists:operations,id',
            'investment_id' => $inversionId ? "nullable|exists:investments,id|required_if:operation_id,{$inversionId}" : 'nullable|exists:investments,id',
            'products' => 'required|array',
            'products.*.product_name' => 'required',
            'products.*.unit_id' => 'required',
            'products.*.price' => 'required',
            'products.*.quantity' => 'required',
            'products.*.months' => 'required'
        ];
    }
}
