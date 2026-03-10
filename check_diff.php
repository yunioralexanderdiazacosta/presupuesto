<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$team_id = 2;
$season_id = 4;

// Verificar ambas temporadas
echo "=== VERIFICANDO AMBAS TEMPORADAS PARA TEAM $team_id ===\n";
foreach ([4, 5] as $sid) {
    $f = (float) DB::table('invoices as i')
        ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
        ->where('i.team_id', $team_id)->where('i.season_id', $sid)
        ->sum(DB::raw('ip.unit_price * ip.amount'));
    
    $cr = (float) DB::table('credit_debit_notes as cdn')
        ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
        ->where('cdn.team_id', $team_id)->where('cdn.season_id', $sid)
        ->whereRaw('LOWER(cdn.type) IN (?, ?)', ['credito', 'nc'])
        ->sum(DB::raw('cdni.unit_price * cdni.quantity'));
    
    $db = (float) DB::table('credit_debit_notes as cdn')
        ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
        ->where('cdn.team_id', $team_id)->where('cdn.season_id', $sid)
        ->whereRaw('LOWER(cdn.type) NOT IN (?, ?)', ['credito', 'nc'])
        ->sum(DB::raw('cdni.unit_price * cdni.quantity'));
    
    $facturadoTotal = $f + $db - $cr;
    
    $c = (float) (DB::table('outflows as o')
        ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
        ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
        ->where('o.season_id', $sid)->where('o.team_id', $team_id)
        ->selectRaw('SUM(CASE WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price ELSE 0 END) as total')
        ->value('total') ?? 0);
    
    echo "Season $sid: Facturas=$".number_format($f,0,',','.').
         " NC=-$".number_format($cr,0,',','.').
         " ND=+$".number_format($db,0,',','.').
         " => Facturado=$".number_format($facturadoTotal,0,',','.').
         " | Consumido=$".number_format($c,0,',','.').
         " | Diff=$".number_format($facturadoTotal-$c,0,',','.')."\n";
}
echo "\n";

// 1. Facturado total
$facturado = (float) DB::table('invoices as i')
    ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
    ->where('i.team_id', $team_id)
    ->where('i.season_id', $season_id)
    ->sum(DB::raw('ip.unit_price * ip.amount'));

// 2. Consumido total
$consumed = (float) DB::table('outflows as o')
    ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
    ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->selectRaw('SUM(CASE
        WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
        WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
        ELSE 0
    END) as total')
    ->value('total') ?? 0;

echo "=== TOTALES ===\n";
echo "Facturado: $" . number_format($facturado, 0, ',', '.') . "\n";
echo "Consumido: $" . number_format($consumed, 0, ',', '.') . "\n";
echo "Diferencia: $" . number_format($facturado - $consumed, 0, ',', '.') . "\n\n";

// 3. Productos de factura con cantidad parcialmente consumida o sin consumir
echo "=== PRODUCTOS DE FACTURA CON DIFERENCIA (no 100% consumidos) ===\n";
$products = DB::select("
    SELECT 
        i.id as invoice_id,
        i.number_document as invoice_number,
        s.name as supplier_name,
        ip.id as invoice_product_id,
        p.name as product_name,
        ip.amount as factura_qty,
        ip.unit_price,
        ROUND(ip.unit_price * ip.amount, 0) as factura_total,
        COALESCE(SUM(o.quantity), 0) as consumed_qty,
        ROUND(COALESCE(SUM(o.quantity), 0) * ip.unit_price, 0) as consumed_total,
        ROUND((ip.amount - COALESCE(SUM(o.quantity), 0)) * ip.unit_price, 0) as diferencia
    FROM invoices i
    JOIN invoice_products ip ON i.id = ip.invoice_id
    LEFT JOIN products p ON ip.product_id = p.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    LEFT JOIN outflows o ON o.invoice_product_id = ip.id
    WHERE i.team_id = ?
      AND i.season_id = ?
    GROUP BY i.id, i.number_document, s.name, ip.id, p.name, ip.amount, ip.unit_price
    HAVING ROUND((ip.amount - COALESCE(SUM(o.quantity), 0)) * ip.unit_price, 0) != 0
    ORDER BY ABS(ROUND((ip.amount - COALESCE(SUM(o.quantity), 0)) * ip.unit_price, 0)) DESC
    LIMIT 30
", [$team_id, $season_id]);

$totalDiff = 0;
foreach ($products as $p) {
    $totalDiff += $p->diferencia;
    echo sprintf(
        "Factura #%s (%s) | Producto: %s | Qty Factura: %s | Qty Consumida: %s | Precio: $%s | Diff: $%s\n",
        $p->invoice_number ?? 'S/N',
        $p->supplier_name ?? 'N/A',
        $p->product_name ?? 'N/A',
        $p->factura_qty,
        $p->consumed_qty,
        number_format($p->unit_price, 0, ',', '.'),
        number_format($p->diferencia, 0, ',', '.')
    );
}
echo "\nTotal diferencia productos: $" . number_format($totalDiff, 0, ',', '.') . "\n\n";

// 4. Notas de crédito/débito
echo "=== NOTAS DE CREDITO/DEBITO ===\n";
$notes = DB::table('credit_debit_notes as cdn')
    ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
    ->leftJoin('suppliers as s', 'cdn.supplier_id', '=', 's.id')
    ->where('cdn.team_id', $team_id)
    ->where('cdn.season_id', $season_id)
    ->select('cdn.id', 'cdn.number', 'cdn.type', 's.name as supplier_name',
        DB::raw('SUM(cdni.unit_price * cdni.quantity) as total'))
    ->groupBy('cdn.id', 'cdn.number', 'cdn.type', 's.name')
    ->get();

foreach ($notes as $n) {
    echo sprintf("Nota #%s | Tipo: %s | Proveedor: %s | Total: $%s\n",
        $n->number, $n->type, $n->supplier_name, number_format($n->total, 0, ',', '.'));
}

// 5. Notas con items sin outflows
echo "\n=== ITEMS DE NOTAS SIN OUTFLOWS ===\n";
$noteItems = DB::select("
    SELECT 
        cdn.id as note_id,
        cdn.number as note_number,
        cdn.type,
        s.name as supplier_name,
        cdni.id as item_id,
        p.name as product_name,
        cdni.quantity,
        cdni.unit_price,
        ROUND(cdni.unit_price * cdni.quantity, 0) as item_total,
        COALESCE(SUM(o.quantity), 0) as consumed_qty,
        ROUND(COALESCE(SUM(o.quantity), 0) * cdni.unit_price, 0) as consumed_total,
        ROUND((cdni.quantity - COALESCE(SUM(o.quantity), 0)) * cdni.unit_price, 0) as diferencia
    FROM credit_debit_notes cdn
    JOIN credit_debit_note_items cdni ON cdn.id = cdni.credit_debit_note_id
    LEFT JOIN products p ON cdni.product_id = p.id
    LEFT JOIN suppliers s ON cdn.supplier_id = s.id
    LEFT JOIN outflows o ON o.credit_debit_note_item_id = cdni.id
    WHERE cdn.team_id = ?
      AND cdn.season_id = ?
    GROUP BY cdn.id, cdn.number, cdn.type, s.name, cdni.id, p.name, cdni.quantity, cdni.unit_price
    HAVING ROUND((cdni.quantity - COALESCE(SUM(o.quantity), 0)) * cdni.unit_price, 0) != 0
    ORDER BY ABS(ROUND((cdni.quantity - COALESCE(SUM(o.quantity), 0)) * cdni.unit_price, 0)) DESC
    LIMIT 30
", [$team_id, $season_id]);

$totalNoteDiff = 0;
foreach ($noteItems as $ni) {
    $totalNoteDiff += $ni->diferencia;
    echo sprintf("Nota #%s (%s - %s) | Producto: %s | Qty: %s | Consumida: %s | Precio: $%s | Diff: $%s\n",
        $ni->note_number, $ni->type, $ni->supplier_name,
        $ni->product_name ?? 'N/A',
        $ni->quantity, $ni->consumed_qty,
        number_format($ni->unit_price, 0, ',', '.'),
        number_format($ni->diferencia, 0, ',', '.'));
}
echo "\nTotal diferencia notas: $" . number_format($totalNoteDiff, 0, ',', '.') . "\n";
