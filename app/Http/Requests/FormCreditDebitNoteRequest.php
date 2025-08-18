<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormCreditDebitNoteRequest extends FormRequest
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
            'type'              => 'required|in:credito,debito',
            'invoice_id'        => 'required|exists:invoices,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'number'            => 'required|string',
            'date'              => 'required|date',
            'reason'            => 'nullable|string',
            'affects_inventory' => 'boolean',
            'items'             => ['required', 'array'],
            'items.*.product_id'=> ['required', 'exists:products,id'],
            'items.*.unit_id'   => ['required', 'exists:units,id'],
            'items.*.quantity'  => ['required', 'numeric'],
            'items.*.unit_price'=> ['required', 'numeric'],
        ];
    }
}
