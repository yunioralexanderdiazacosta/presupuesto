<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * El "Impuesto Específico" (ajuste MEPCO) es el único producto que admite cantidades negativas
 * (en facturas, notas y salidas). Se detecta por nombre, igual que en FormProducts.vue.
 */
class SpecificTax
{
    public static function isName(?string $name): bool
    {
        return (bool) preg_match('/impuesto\s+espec[ií]fico/i', (string) $name);
    }

    public static function isProduct($productId): bool
    {
        if (!$productId) {
            return false;
        }
        return self::isName(DB::table('products')->where('id', $productId)->value('name'));
    }

    public static function isInvoiceProduct($invoiceProductId): bool
    {
        if (!$invoiceProductId) {
            return false;
        }
        return self::isName(
            DB::table('invoice_products')
                ->join('products', 'invoice_products.product_id', '=', 'products.id')
                ->where('invoice_products.id', $invoiceProductId)
                ->value('products.name')
        );
    }
}
