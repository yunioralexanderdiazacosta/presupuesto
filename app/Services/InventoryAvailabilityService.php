<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fuente única del stock disponible por lote (línea de factura o ítem de nota de débito).
 *
 * Disponible factura = cantidad facturada − consumos (outflows) − devoluciones físicas (NC que afectan inventario).
 * Disponible ND      = cantidad de la ND (solo si afecta inventario) − consumos (outflows).
 *
 * `outflows` es la tabla maestra del kardex: los consumos de agroquímicos, fertilizantes y
 * combustible también generan su registro ahí.
 */
class InventoryAvailabilityService
{
    public const EPSILON = 0.0001;

    /**
     * @param int[] $excludeOutflowIds consumos a ignorar (útil al editar un consumo existente)
     * @param int[] $excludeNoteIds    notas a ignorar
     */
    public static function forInvoiceProduct(int $invoiceProductId, array $excludeOutflowIds = [], bool $lock = false, array $excludeNoteIds = []): array
    {
        $query = DB::table('invoice_products')
            ->leftJoin('products', 'invoice_products.product_id', '=', 'products.id')
            ->where('invoice_products.id', $invoiceProductId)
            ->select('invoice_products.id', 'invoice_products.amount', 'invoice_products.invoice_id', 'products.name as product_name');

        if ($lock) {
            $query->lockForUpdate();
        }

        $line = $query->first();
        if (!$line) {
            throw ValidationException::withMessages(['stock' => 'La línea de factura seleccionada no existe.']);
        }

        $consumed = (float) DB::table('outflows')
            ->where('invoice_product_id', $invoiceProductId)
            ->when($excludeOutflowIds, fn($q) => $q->whereNotIn('id', $excludeOutflowIds))
            ->sum('quantity');

        $returned = (float) DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_note_items.invoice_product_id', $invoiceProductId)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->when($excludeNoteIds, fn($q) => $q->whereNotIn('credit_debit_notes.id', $excludeNoteIds))
            ->sum('credit_debit_note_items.quantity');

        $original = (float) ($line->amount ?? 0);

        return [
            'product_name' => $line->product_name ?? 'Producto',
            'invoice_id'   => $line->invoice_id,
            'original'     => $original,
            'consumed'     => round($consumed, 4),
            'returned'     => round($returned, 4),
            'available'    => round($original - $consumed - $returned, 4),
        ];
    }

    public static function forDebitNoteItem(int $itemId, array $excludeOutflowIds = [], bool $lock = false): array
    {
        $query = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->leftJoin('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->where('credit_debit_note_items.id', $itemId)
            ->select(
                'credit_debit_note_items.quantity',
                'credit_debit_notes.type',
                'credit_debit_notes.affects_inventory',
                'products.name as product_name'
            );

        if ($lock) {
            $query->lockForUpdate();
        }

        $item = $query->first();
        if (!$item) {
            throw ValidationException::withMessages(['stock' => 'El ítem de nota de débito seleccionado no existe.']);
        }

        // Una ND que solo corrige precio no ingresa mercadería: no hay nada que consumir de ella.
        $original = ($item->type === 'debito' && $item->affects_inventory) ? (float) $item->quantity : 0.0;

        $consumed = (float) DB::table('outflows')
            ->where('credit_debit_note_item_id', $itemId)
            ->when($excludeOutflowIds, fn($q) => $q->whereNotIn('id', $excludeOutflowIds))
            ->sum('quantity');

        return [
            'product_name' => $item->product_name ?? 'Producto',
            'original'     => $original,
            'consumed'     => round($consumed, 4),
            'returned'     => 0.0,
            'available'    => round($original - $consumed, 4),
        ];
    }

    /**
     * Valida que las cantidades solicitadas a consumir no superen el disponible de cada lote.
     * Debe llamarse dentro de una transacción para que el bloqueo evite consumos simultáneos.
     *
     * Al editar, $previous* indica lo que ya consumían los registros excluidos en cada lote: si la
     * cantidad nueva no supera la anterior, no se bloquea (permite corregir a la baja lotes que ya
     * quedaron negativos por datos antiguos).
     *
     * @param array<int,float> $byInvoiceProduct invoice_product_id => cantidad solicitada
     * @param array<int,float> $byDebitNoteItem  credit_debit_note_item_id => cantidad solicitada
     * @param array<int,float> $previousByInvoiceProduct invoice_product_id => cantidad consumida antes de editar
     * @param array<int,float> $previousByDebitNoteItem  credit_debit_note_item_id => cantidad consumida antes de editar
     */
    public static function assertCanConsume(
        array $byInvoiceProduct,
        array $byDebitNoteItem = [],
        array $excludeOutflowIds = [],
        string $errorKey = 'stock',
        array $previousByInvoiceProduct = [],
        array $previousByDebitNoteItem = []
    ): void {
        $errors = [];

        ksort($byInvoiceProduct);
        foreach ($byInvoiceProduct as $ipId => $requested) {
            $stock = self::forInvoiceProduct((int) $ipId, $excludeOutflowIds, true);
            $isIncrease = $requested - (float) ($previousByInvoiceProduct[$ipId] ?? 0) > self::EPSILON;
            if ($isIncrease && $requested - $stock['available'] > self::EPSILON) {
                $errors[] = self::consumeMessage($stock, $requested);
            }
        }

        ksort($byDebitNoteItem);
        foreach ($byDebitNoteItem as $itemId => $requested) {
            $stock = self::forDebitNoteItem((int) $itemId, $excludeOutflowIds, true);
            $isIncrease = $requested - (float) ($previousByDebitNoteItem[$itemId] ?? 0) > self::EPSILON;
            if ($isIncrease && $requested - $stock['available'] > self::EPSILON) {
                $errors[] = self::consumeMessage($stock, $requested);
            }
        }

        if ($errors) {
            throw ValidationException::withMessages([$errorKey => implode(' ', $errors)]);
        }
    }

    /**
     * Valida que una NC con devolución física no devuelva más de lo que queda en bodega.
     * Debe llamarse dentro de una transacción.
     *
     * @param array<int,float> $byInvoiceProduct invoice_product_id => cantidad a devolver
     */
    public static function assertCanReturn(array $byInvoiceProduct, bool $isAnnulment = false): void
    {
        $errors = [];

        ksort($byInvoiceProduct);
        foreach ($byInvoiceProduct as $ipId => $requested) {
            $stock = self::forInvoiceProduct((int) $ipId, [], true);
            if ($requested - $stock['available'] > self::EPSILON) {
                $errors[] = sprintf(
                    '%s: se intenta devolver %s y solo hay %s disponible.',
                    $stock['product_name'],
                    self::fmt($requested),
                    self::fmt(max(0, $stock['available']))
                );
            }
        }

        if ($errors) {
            $hint = $isAnnulment
                ? 'Si es solo una rebaja de precio, quite el check de «Anula factura completa» y «Afecta inventario». Si no, elimine las salidas.'
                : 'Si es solo una rebaja de precio, quite el check de «Afecta inventario». Si no, elimine las salidas.';

            throw ValidationException::withMessages(['items' => implode(' ', $errors) . ' ' . $hint]);
        }
    }

    private static function consumeMessage(array $stock, float $requested): string
    {
        return sprintf(
            'Stock insuficiente para %s: solicitado %s, disponible %s.',
            $stock['product_name'],
            self::fmt($requested),
            self::fmt(max(0, $stock['available']))
        );
    }

    private static function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, ',', '.'), '0'), ',');
    }
}
