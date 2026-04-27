<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class FormCreditDebitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'              => 'required|in:credito,debito',
            'invoice_id'        => $this->type === 'credito' ? 'required|exists:invoices,id' : 'nullable|exists:invoices,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'number'            => 'required|string',
            'date'              => 'required|date',
            'reason'            => 'nullable|string',
            'affects_inventory' => 'boolean',
            'is_annulment'      => 'boolean',
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id'=> ['required', 'exists:products,id'],
            'items.*.unit_id'   => ['required', 'exists:units,id'],
            'items.*.quantity'  => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'=> ['required', 'numeric', 'min:0'],
            'items.*.invoice_product_id' => ['nullable', 'exists:invoice_products,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->type !== 'credito') return;

            $invoiceId = $this->invoice_id;
            if (!$invoiceId) return;

            // Validar que cada item no exceda la cantidad facturada
            foreach ($this->items ?? [] as $idx => $item) {
                if (empty($item['invoice_product_id'])) continue;
                $ip = DB::table('invoice_products')
                    ->where('id', $item['invoice_product_id'])
                    ->where('invoice_id', $invoiceId)
                    ->first();
                if ($ip && $item['quantity'] > $ip->amount) {
                    $validator->errors()->add(
                        "items.{$idx}.quantity",
                        "La cantidad ({$item['quantity']}) excede la cantidad facturada ({$ip->amount})."
                    );
                }
            }

            // Validar que el monto total de la NC no exceda el total de la factura
            $invoiceTotal = DB::table('invoice_products')
                ->where('invoice_id', $invoiceId)
                ->selectRaw('SUM(amount * unit_price) as total')
                ->value('total') ?? 0;

            $noteTotal = collect($this->items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));

            if ($noteTotal > $invoiceTotal) {
                $validator->errors()->add(
                    'items',
                    "El monto total de la nota (\${$noteTotal}) excede el total de la factura (\${$invoiceTotal})."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.min'              => 'Debe agregar al menos un item.',
            'items.*.quantity.gt'    => 'La cantidad debe ser mayor a cero.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
        ];
    }
}
