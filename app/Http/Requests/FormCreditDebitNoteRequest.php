<?php

namespace App\Http\Requests;

use App\Support\SpecificTax;
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
            'invoice_id'        => 'required|exists:invoices,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'number'            => 'required|string',
            'date'              => 'required|date',
            'reason'            => 'nullable|string',
            'affects_inventory' => 'boolean',
            'is_annulment'      => 'boolean',
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id'=> ['required', 'exists:products,id'],
            'items.*.unit_id'   => ['required', 'exists:units,id'],
            'items.*.quantity'  => ['required', 'numeric', function ($attribute, $value, $fail) {
                if ((float) $value == 0.0) {
                    $fail('La cantidad debe ser distinta de cero.');
                    return;
                }
                // Solo el Impuesto Específico admite cantidad negativa (reversa de una línea negativa de la factura)
                if ((float) $value < 0) {
                    preg_match('/items\.(\d+)\.quantity/', $attribute, $m);
                    $invoiceProductId = $this->input('items.' . ($m[1] ?? 0) . '.invoice_product_id');
                    if (!SpecificTax::isInvoiceProduct($invoiceProductId)) {
                        $fail('Solo el Impuesto Específico admite cantidad negativa.');
                    }
                }
            }],
            'items.*.unit_price'=> ['required', 'numeric', 'min:0'],
            'items.*.invoice_product_id' => ['required', 'exists:invoice_products,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // ND: cada ítem se basa en una línea de la factura (de ahí salen producto y sucursal)
            if ($this->type === 'debito' && $this->invoice_id) {
                foreach ($this->items ?? [] as $idx => $item) {
                    if (empty($item['invoice_product_id'])) continue;
                    $belongs = DB::table('invoice_products')
                        ->where('id', $item['invoice_product_id'])
                        ->where('invoice_id', $this->invoice_id)
                        ->exists();
                    if (!$belongs) {
                        $validator->errors()->add(
                            "items.{$idx}.invoice_product_id",
                            'La línea seleccionada no pertenece a la factura indicada.'
                        );
                    }
                }
            }

            if ($this->type !== 'credito') return;

            $invoiceId = $this->invoice_id;
            if (!$invoiceId) return;

            // Validar que cada item pertenezca a la factura y no exceda la cantidad facturada
            $isFinancial = !$this->boolean('affects_inventory') && !$this->boolean('is_annulment');

            // La NC de solo precio reescribe el costo de la factura original (incluido lo ya consumido):
            // no se permite si esa factura pertenece a una temporada cerrada.
            if ($isFinancial && self::invoiceSeasonIsLocked($invoiceId)) {
                $validator->errors()->add(
                    'invoice_id',
                    'La factura pertenece a una temporada cerrada: una nota de crédito de solo precio cambiaría los costos ya cerrados de esa temporada. Para registrarla, primero se debe reabrir la temporada.'
                );
                return;
            }

            $amountByLine = [];
            foreach ($this->items ?? [] as $idx => $item) {
                if (empty($item['invoice_product_id'])) continue;
                $ip = DB::table('invoice_products')
                    ->where('id', $item['invoice_product_id'])
                    ->where('invoice_id', $invoiceId)
                    ->first();
                if (!$ip) {
                    $validator->errors()->add(
                        "items.{$idx}.invoice_product_id",
                        'La línea seleccionada no pertenece a la factura indicada.'
                    );
                    continue;
                }
                // La cantidad de la nota debe tener el signo de la línea facturada (línea negativa = nota negativa)
                $qty = (float) $item['quantity'];
                $lineAmount = (float) $ip->amount;
                if (($lineAmount < 0) !== ($qty < 0)) {
                    $validator->errors()->add(
                        "items.{$idx}.quantity",
                        "La cantidad ({$item['quantity']}) debe tener el mismo signo que la cantidad facturada ({$ip->amount})."
                    );
                } elseif (abs($qty) > abs($lineAmount)) {
                    $validator->errors()->add(
                        "items.{$idx}.quantity",
                        "La cantidad ({$item['quantity']}) excede la cantidad facturada ({$ip->amount})."
                    );
                }
                $amountByLine[$ip->id] = ($amountByLine[$ip->id] ?? 0)
                    + (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            }

            // NC de solo precio: la rebaja de cada línea no puede superar su valor actual (evita precios negativos)
            if ($isFinancial) {
                foreach ($amountByLine as $ipId => $ncAmount) {
                    $ip = DB::table('invoice_products')
                        ->join('products', 'invoice_products.product_id', '=', 'products.id')
                        ->where('invoice_products.id', $ipId)
                        ->select('invoice_products.amount', 'invoice_products.unit_price', 'products.name')
                        ->first();
                    $lineValue = round((float) $ip->amount * (float) $ip->unit_price, 2);
                    if ($ncAmount - $lineValue > 0.01) {
                        $validator->errors()->add(
                            'items',
                            "La rebaja para {$ip->name} (\${$ncAmount}) supera el valor vigente de la línea (\${$lineValue})."
                        );
                    }
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

    public static function invoiceSeasonIsLocked($invoiceId): bool
    {
        return DB::table('invoices')
            ->join('seasons', 'invoices.season_id', '=', 'seasons.id')
            ->where('invoices.id', $invoiceId)
            ->where('seasons.is_locked', 1)
            ->exists();
    }

    public function messages(): array
    {
        return [
            'items.min'              => 'Debe agregar al menos un item.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'items.*.invoice_product_id.required' => 'Seleccione la línea de factura a la que corresponde la nota de crédito.',
        ];
    }
}
