<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormInvoiceRequest extends FormRequest
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
        $rules = [
            'payment_term'      => 'required',
            'payment_type'      => 'required',
            'supplier_id'       => 'required',
            'company_reason_id' => 'required',
            'type_document_id'  => 'required',
            'number_document'   => [
                'required',
                Rule::unique('invoices')
                    ->where('supplier_id', $this->supplier_id)
                    ->where('team_id', auth()->user()->team_id)
                    ->ignore($this->route('invoice'))
            ],
            'date'              => 'required',
            'due_date'          => 'required',
            'month_id'          => 'required|exists:months,id',
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required'],
            'products.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'products.*.amount' => ['required', 'numeric'],
            'products.*.observations' => ['nullable', 'string'],
            'products.*.branch_id'    => ['nullable', 'integer', 'exists:branches,id'],
            'expense_item_id'   => ['nullable', 'integer', 'exists:expense_report_items,id'],
            'expense_item_ids'  => ['nullable', 'array'],
            'expense_item_ids.*' => ['integer', 'exists:expense_report_items,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'number_document.unique' => 'Ya existe una factura con este número para el proveedor seleccionado.',
        ];
    }
}
