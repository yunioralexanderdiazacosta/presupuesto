<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$team_id = 1;
$season_id = 4;

// 1. Facturas con NC asociada (por invoice_id en credit_debit_notes)
echo "=== ESTRUCTURA DE CREDIT_DEBIT_NOTES ===\n";
$cols = collect(DB::select('SHOW COLUMNS FROM credit_debit_notes'))->pluck('Field');
echo "Columnas: " . $cols->join(', ') . "\n\n";

// 2. NC que tienen invoice_id (vinculadas a factura específica)
echo "=== NOTAS DE CREDITO CON invoice_id ===\n";
$ncs = DB::table('credit_debit_notes as cdn')
    ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
    ->leftJoin('suppliers as s', 'cdn.supplier_id', '=', 's.id')
    ->where('cdn.team_id', $team_id)
    ->where('cdn.season_id', $season_id)
    ->whereRaw('LOWER(cdn.type) IN (?, ?)', ['credito', 'nc'])
    ->select('cdn.id', 'cdn.number', 'cdn.invoice_id', 'cdn.is_annulment', 's.name as supplier',
        DB::raw('SUM(cdni.unit_price * cdni.quantity) as total'))
    ->groupBy('cdn.id', 'cdn.number', 'cdn.invoice_id', 'cdn.is_annulment', 's.name')
    ->get();

$totalNC = 0;
foreach ($ncs as $nc) {
    $totalNC += $nc->total;
    echo sprintf("NC #%s | invoice_id: %s | is_annulment: %s | %s | Total: $%s\n",
        $nc->number, $nc->invoice_id ?? 'NULL', $nc->is_annulment, $nc->supplier,
        number_format($nc->total, 0, ',', '.'));
}
echo "Total NC: $" . number_format($totalNC, 0, ',', '.') . "\n\n";

// 3. Encontrar productos SIN outflow, separando los que tienen NC y los que no
echo "=== PRODUCTOS SIN OUTFLOW - DETALLE ===\n";
$prodsNoOutflow = DB::select("
    SELECT 
        i.id as invoice_id,
        i.number_document,
        COALESCE(s.name, 'N/A') as supplier_name,
        COALESCE(p.name, 'N/A') as product_name,
        ip.amount as qty,
        ip.unit_price,
        ROUND(ip.unit_price * ip.amount) as total,
        (SELECT COUNT(*) FROM credit_debit_notes cdn 
         WHERE cdn.invoice_id = i.id 
         AND cdn.team_id = i.team_id
         AND LOWER(cdn.type) IN ('credito', 'nc')) as tiene_nc
    FROM invoices i
    JOIN invoice_products ip ON i.id = ip.invoice_id
    LEFT JOIN products p ON ip.product_id = p.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    LEFT JOIN outflows o ON o.invoice_product_id = ip.id
    WHERE i.team_id = ?
      AND i.season_id = ?
    GROUP BY i.id, i.number_document, s.name, ip.id, p.name, ip.amount, ip.unit_price
    HAVING COALESCE(SUM(o.quantity), 0) = 0
    ORDER BY total DESC
", [$team_id, $season_id]);

$conNC = 0;
$sinNC = 0;
$detalleSinNC = [];
foreach ($prodsNoOutflow as $p) {
    if ($p->tiene_nc > 0) {
        $conNC += $p->total;
    } else {
        $sinNC += $p->total;
        $detalleSinNC[] = $p;
    }
}
echo "Productos sin outflow CON nota de crédito: $" . number_format($conNC, 0, ',', '.') . "\n";
echo "Productos sin outflow SIN nota de crédito: $" . number_format($sinNC, 0, ',', '.') . "\n\n";

echo "=== DETALLE: Productos sin outflow Y sin NC ===\n";
foreach ($detalleSinNC as $p) {
    echo sprintf("Doc #%s | %s | %s | Qty: %s | P.U: $%s | Total: $%s\n",
        $p->number_document, $p->supplier_name, $p->product_name,
        $p->qty, number_format($p->unit_price, 0, ',', '.'),
        number_format($p->total, 0, ',', '.'));
}
echo "Subtotal sin NC: $" . number_format($sinNC, 0, ',', '.') . "\n\n";

// 4. Productos con outflow PARCIAL (consumido < facturado)
echo "=== PRODUCTOS CON CONSUMO PARCIAL ===\n";
$parciales = DB::select("
    SELECT 
        i.number_document,
        COALESCE(s.name, 'N/A') as supplier_name,
        COALESCE(p.name, 'N/A') as product_name,
        ip.amount as qty_factura,
        SUM(o.quantity) as qty_consumida,
        ip.unit_price,
        ROUND((ip.amount - SUM(o.quantity)) * ip.unit_price) as diferencia
    FROM invoices i
    JOIN invoice_products ip ON i.id = ip.invoice_id
    LEFT JOIN products p ON ip.product_id = p.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    JOIN outflows o ON o.invoice_product_id = ip.id
    WHERE i.team_id = ?
      AND i.season_id = ?
    GROUP BY i.id, i.number_document, s.name, ip.id, p.name, ip.amount, ip.unit_price
    HAVING SUM(o.quantity) < ip.amount AND ROUND((ip.amount - SUM(o.quantity)) * ip.unit_price) != 0
    ORDER BY ABS(ROUND((ip.amount - SUM(o.quantity)) * ip.unit_price)) DESC
", [$team_id, $season_id]);

$totalParcial = 0;
foreach ($parciales as $p) {
    $totalParcial += $p->diferencia;
    echo sprintf("Doc #%s | %s | %s | QtyFact: %s | QtyCons: %s | P.U: $%s | Diff: $%s\n",
        $p->number_document, $p->supplier_name, $p->product_name,
        $p->qty_factura, $p->qty_consumida,
        number_format($p->unit_price, 0, ',', '.'),
        number_format($p->diferencia, 0, ',', '.'));
}
echo "Total parciales: $" . number_format($totalParcial, 0, ',', '.') . "\n\n";

// 5. Verificar si hay NC que NO tienen invoice_id (no vinculadas a factura)
echo "=== NC SIN INVOICE_ID (sueltas) ===\n";
$ncsSueltas = DB::table('credit_debit_notes as cdn')
    ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
    ->leftJoin('suppliers as s', 'cdn.supplier_id', '=', 's.id')
    ->where('cdn.team_id', $team_id)
    ->where('cdn.season_id', $season_id)
    ->whereRaw('LOWER(cdn.type) IN (?, ?)', ['credito', 'nc'])
    ->whereNull('cdn.invoice_id')
    ->select('cdn.id', 'cdn.number', 's.name as supplier',
        DB::raw('SUM(cdni.unit_price * cdni.quantity) as total'))
    ->groupBy('cdn.id', 'cdn.number', 's.name')
    ->get();

foreach ($ncsSueltas as $nc) {
    echo sprintf("NC #%s | %s | Total: $%s\n",
        $nc->number, $nc->supplier, number_format($nc->total, 0, ',', '.'));
}
if ($ncsSueltas->isEmpty()) echo "(ninguna)\n";

// 6. Resumen final
echo "\n=== RESUMEN DE LA DIFERENCIA ===\n";
echo "Productos sin outflow SIN NC: $" . number_format($sinNC, 0, ',', '.') . "\n";
echo "Productos con consumo parcial: $" . number_format($totalParcial, 0, ',', '.') . "\n";
echo "Total explicado: $" . number_format($sinNC + $totalParcial, 0, ',', '.') . "\n";
echo "Diferencia real dashboard: $35.546\n";
