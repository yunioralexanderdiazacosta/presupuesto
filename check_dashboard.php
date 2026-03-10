<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Primero mostrar todos los combos con datos
echo "=== COMBINACIONES CON DATOS ===\n";
$combos = DB::select("
    SELECT i.team_id, t.name as team_name, i.season_id, s.name as season_name,
           COUNT(*) as invoices, 
           ROUND(SUM(sub.total)) as facturado
    FROM invoices i
    JOIN teams t ON i.team_id = t.id
    JOIN seasons s ON i.season_id = s.id
    JOIN (
        SELECT ip.invoice_id, SUM(ip.unit_price * ip.amount) as total
        FROM invoice_products ip
        GROUP BY ip.invoice_id
    ) sub ON i.id = sub.invoice_id
    GROUP BY i.team_id, t.name, i.season_id, s.name
    ORDER BY facturado DESC
");
foreach ($combos as $c) {
    echo "Team {$c->team_id} ({$c->team_name}) | Season {$c->season_id} ({$c->season_name}) | {$c->invoices} facturas | Facturado: $" . number_format($c->facturado, 0, ',', '.') . "\n";
}
echo "\n";

$team_id = 1;
$season_id = 4;

// === FACTURADO ===
$invoicesTotal = (float) DB::table('invoices as i')
    ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
    ->where('i.team_id', $team_id)
    ->where('i.season_id', $season_id)
    ->sum(DB::raw('ip.unit_price * ip.amount'));

$creditNotesTotal = (float) DB::table('credit_debit_notes as cdn')
    ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
    ->where('cdn.team_id', $team_id)
    ->where('cdn.season_id', $season_id)
    ->whereRaw('LOWER(cdn.type) IN (?, ?)', ['credito', 'nc'])
    ->sum(DB::raw('cdni.unit_price * cdni.quantity'));

$debitNotesTotal = (float) DB::table('credit_debit_notes as cdn')
    ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
    ->where('cdn.team_id', $team_id)
    ->where('cdn.season_id', $season_id)
    ->whereRaw('LOWER(cdn.type) NOT IN (?, ?)', ['credito', 'nc'])
    ->sum(DB::raw('cdni.unit_price * cdni.quantity'));

$notesTotal = $debitNotesTotal - $creditNotesTotal;
$invoicedTotal = $invoicesTotal + $notesTotal;

// === CONSUMIDO ===
$consumedWithInv = (float) (DB::table('outflows as o')
    ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
    ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->selectRaw('SUM(CASE
        WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
        WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
        ELSE 0
    END) as total')
    ->value('total') ?? 0);

$consumedInvOnly = (float) (DB::table('outflows as o')
    ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
    ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
    ->join('operations as op', 'o.operation_id', '=', 'op.id')
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
    ->selectRaw('SUM(CASE
        WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
        WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
        ELSE 0
    END) as total')
    ->value('total') ?? 0);

$consumedSinInv = $consumedWithInv - $consumedInvOnly;

echo "=== VALORES CRUDOS DEL BACKEND ===\n";
echo "invoicesTotal (facturas):     $" . number_format($invoicesTotal, 0, ',', '.') . "\n";
echo "creditNotesTotal (NC):        $" . number_format($creditNotesTotal, 0, ',', '.') . "\n";
echo "debitNotesTotal (ND):         $" . number_format($debitNotesTotal, 0, ',', '.') . "\n";
echo "notesTotal (ND-NC):           $" . number_format($notesTotal, 0, ',', '.') . "\n";
echo "invoicedTotal:                $" . number_format($invoicedTotal, 0, ',', '.') . "\n";
echo "\n";
echo "consumedWithInvestments:      $" . number_format($consumedWithInv, 0, ',', '.') . "\n";
echo "consumedInvestmentsOnly:      $" . number_format($consumedInvOnly, 0, ',', '.') . "\n";
echo "consumedSinInversiones:       $" . number_format($consumedSinInv, 0, ',', '.') . "\n";

echo "\n=== LO QUE VE EL USUARIO EN EL DASHBOARD ===\n";

echo "\n--- CON inversiones (toggle ON) ---\n";
$dispInvoicedOn = $invoicedTotal;
$dispConsumedOn = $consumedWithInv;
echo "Facturado:  $" . number_format($dispInvoicedOn, 0, ',', '.') . "\n";
echo "Consumido:  $" . number_format($dispConsumedOn, 0, ',', '.') . "\n";
echo "Diferencia: $" . number_format($dispInvoicedOn - $dispConsumedOn, 0, ',', '.') . "\n";

echo "\n--- SIN inversiones (toggle OFF) ---\n";
$dispInvoicedOff = $invoicedTotal - $consumedInvOnly;
$dispConsumedOff = $consumedSinInv;
echo "Facturado:  $" . number_format($dispInvoicedOff, 0, ',', '.') . "\n";
echo "Consumido:  $" . number_format($dispConsumedOff, 0, ',', '.') . "\n";
echo "Diferencia: $" . number_format($dispInvoicedOff - $dispConsumedOff, 0, ',', '.') . "\n";

// === BUSCAR PRODUCTOS CON DIFERENCIA ===
echo "\n=== PRODUCTOS DE FACTURA CON DIFERENCIA ===\n";
$products = DB::select("
    SELECT 
        i.id as invoice_id,
        i.number_document,
        COALESCE(s.name, 'N/A') as supplier_name,
        COALESCE(p.name, 'N/A') as product_name,
        ip.amount as factura_qty,
        ip.unit_price,
        ROUND(ip.unit_price * ip.amount) as factura_total,
        COALESCE(SUM(o.quantity), 0) as consumed_qty,
        ROUND(COALESCE(SUM(o.quantity), 0) * ip.unit_price) as consumed_total
    FROM invoices i
    JOIN invoice_products ip ON i.id = ip.invoice_id
    LEFT JOIN products p ON ip.product_id = p.id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    LEFT JOIN outflows o ON o.invoice_product_id = ip.id
    WHERE i.team_id = ?
      AND i.season_id = ?
    GROUP BY i.id, i.number_document, s.name, ip.id, p.name, ip.amount, ip.unit_price
    HAVING ROUND((ip.amount - COALESCE(SUM(o.quantity), 0)) * ip.unit_price) != 0
    ORDER BY ABS(ROUND((ip.amount - COALESCE(SUM(o.quantity), 0)) * ip.unit_price)) DESC
    LIMIT 20
", [$team_id, $season_id]);

$totalDiff = 0;
foreach ($products as $p) {
    $diff = $p->factura_total - $p->consumed_total;
    $totalDiff += $diff;
    echo sprintf("Doc #%s | %s | %s | QtyFact: %s | QtyCons: %s | P.U: $%s | Diff: $%s\n",
        $p->number_document, $p->supplier_name, $p->product_name,
        $p->factura_qty, $p->consumed_qty,
        number_format($p->unit_price, 0, ',', '.'),
        number_format($diff, 0, ',', '.'));
}
echo "Total diferencia productos: $" . number_format($totalDiff, 0, ',', '.') . "\n";

// === OUTFLOWS SIN FACTURA NI NOTA ===
echo "\n=== OUTFLOWS HUERFANOS (sin invoice_product ni note_item) ===\n";
$orphans = DB::table('outflows as o')
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->whereNull('o.invoice_product_id')
    ->whereNull('o.credit_debit_note_item_id')
    ->count();
echo "Outflows sin factura ni nota: $orphans\n";

// === OUTFLOWS CON NOTA DE CREDITO ===
echo "\n=== OUTFLOWS DE NOTAS (credito/debito) ===\n";
$noteOutflows = DB::select("
    SELECT 
        cdn.type,
        COUNT(o.id) as qty_outflows,
        ROUND(SUM(o.quantity * cdni.unit_price)) as total
    FROM outflows o
    JOIN credit_debit_note_items cdni ON o.credit_debit_note_item_id = cdni.id
    JOIN credit_debit_notes cdn ON cdni.credit_debit_note_id = cdn.id
    WHERE o.season_id = ?
      AND o.team_id = ?
    GROUP BY cdn.type
", [$season_id, $team_id]);

foreach ($noteOutflows as $no) {
    echo "Tipo: {$no->type} | Outflows: {$no->qty_outflows} | Total: $" . number_format($no->total, 0, ',', '.') . "\n";
}
if (empty($noteOutflows)) echo "(ninguno)\n";
