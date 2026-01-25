<?php

namespace App\Http\Requests\InvoicePayments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoicePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:1,2,3',
            'bank_id' => 'required_if:payment_method,1|nullable|exists:banks,id',
            'transaction_number' => 'required_if:payment_method,1,3|nullable|string|max:255',
            'observations' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'payment_date.date' => 'La fecha de pago no es válida.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in' => 'El método de pago no es válido.',
            'bank_id.required_if' => 'El banco es obligatorio para transferencias.',
            'bank_id.exists' => 'El banco seleccionado no existe.',
            'transaction_number.required_if' => 'El número de transacción es obligatorio para transferencias y cheques.',
            'transaction_number.max' => 'El número de transacción no puede exceder 255 caracteres.',
            'observations.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ];
    }
}
