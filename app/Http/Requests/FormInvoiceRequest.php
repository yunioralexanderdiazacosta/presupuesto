<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'payment_term'      => 'required',
            'payment_type'      => 'required',
            'supplier_id'       => 'required',
            'company_reason_id' => 'required',
            'type_document_id'  => 'required',
            'number_document'   => 'required',
            'date'              => 'required',
            'due_date'          => 'required',
            'products'          => "required|array|min:1"  
        ];
    }
}
