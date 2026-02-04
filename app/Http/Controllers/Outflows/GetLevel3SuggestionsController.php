<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetLevel3SuggestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $productId = $request->input('product_id');
        
        if (!$productId) {
            return response()->json([]);
        }

        // Buscar los level3_id más usados para este producto
        $suggestions = DB::table('outflows as o')
            ->join('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
            ->join('level3s as l3', 'o.level3_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('ip.product_id', $productId)
            ->whereNotNull('o.level3_id')
            ->select(
                'o.level3_id',
                'l3.name as level3_name',
                'l2.name as level2_name',
                'l1.name as level1_name',
                DB::raw('COUNT(*) as usage_count')
            )
            ->groupBy('o.level3_id', 'l3.name', 'l2.name', 'l1.name')
            ->orderByDesc('usage_count')
            ->limit(3)
            ->get();

        return response()->json($suggestions);
    }
}
