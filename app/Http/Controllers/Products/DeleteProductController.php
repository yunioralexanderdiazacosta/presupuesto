<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductController extends Controller
{
    public function __invoke(Product $product)
    {
        // Verificar si el producto está siendo usado
        $usageChecks = [
            'invoice_products' => DB::table('invoice_products')->where('product_id', $product->id)->count(),
            'fertilizer_order_product' => DB::table('fertilizer_order_product')->where('product_id', $product->id)->count(),
            'application_order_product' => DB::table('application_order_product')->where('product_id', $product->id)->exists() ? 1 : 0,
        ];
        
        $totalUsages = array_sum($usageChecks);
        
        if ($totalUsages > 0) {
            $messages = [];
            if ($usageChecks['invoice_product'] > 0) {
                $messages[] = "Facturas: {$usageChecks['invoice_product']}";
            }
            if ($usageChecks['fertilizer_order_product'] > 0) {
                $messages[] = "Órdenes de fertilizante: {$usageChecks['fertilizer_order_product']}";
            }
            if ($usageChecks['application_order_product'] > 0) {
                $messages[] = "Órdenes de aplicación: {$usageChecks['application_order_product']}";
            }
            
            return back()->withErrors([
                'error' => "No se puede eliminar el producto '{$product->name}' porque está siendo usado en: " . implode(', ', $messages) . ". Elimine primero estos registros."
            ]);
        }
        
        $product->delete();
        
        return back()->with('success', 'Producto eliminado correctamente');
    }
}
