<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$teamId = 2;

// Solo PETROLEO (no bencina)
$rows = DB::select("
    SELECT ip.id as ip_id, ip.amount, ip.unit_price, i.number_document, i.id as inv_id, i.season_id
    FROM invoice_products ip
    JOIN products p ON ip.product_id = p.id
    JOIN invoices i ON ip.invoice_id = i.id
    WHERE LOWER(p.name) LIKE '%petroleo%'
    AND i.team_id = ?
    ORDER BY ip.id
", [$teamId]);

echo "=== LINEAS DE FACTURA DE PETROLEO (team $teamId) ===" . PHP_EOL;
echo str_pad('IP_ID', 8) . str_pad('FACTURA', 12) . str_pad('SEASON', 8) . str_pad('CANTIDAD', 12) . 'PRECIO' . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

$total = 0;
foreach ($rows as $r) {
    echo str_pad($r->ip_id, 8) . str_pad($r->number_document, 12) . str_pad($r->season_id, 8) . str_pad($r->amount, 12) . $r->unit_price . PHP_EOL;
    $total += $r->amount;
}
echo PHP_EOL . "Total petroleo en facturas: $total lts (" . count($rows) . " registros)" . PHP_EOL;

// Salidas de petroleo en fuel_outflows
$fuelOut = DB::select("
    SELECT COALESCE(SUM(fo.liters), 0) as total, COUNT(*) as registros
    FROM fuel_outflows fo
    JOIN invoice_products ip ON fo.invoice_product_id = ip.id
    JOIN products p ON ip.product_id = p.id
    WHERE LOWER(p.name) LIKE '%petroleo%'
    AND fo.team_id = ?
", [$teamId]);
echo PHP_EOL . "Total petroleo consumido (fuel_outflows): " . $fuelOut[0]->total . " lts (" . $fuelOut[0]->registros . " reg)" . PHP_EOL;

// Salidas de petroleo en outflows
$outflowOut = DB::select("
    SELECT COALESCE(SUM(o.quantity), 0) as total, COUNT(*) as registros
    FROM outflows o
    JOIN invoice_products ip ON o.invoice_product_id = ip.id
    JOIN products p ON ip.product_id = p.id
    WHERE LOWER(p.name) LIKE '%petroleo%'
    AND o.team_id = ?
", [$teamId]);
echo "Total petroleo consumido (outflows): " . $outflowOut[0]->total . " lts (" . $outflowOut[0]->registros . " reg)" . PHP_EOL;

echo PHP_EOL . "=== STOCK PETROLEO ===" . PHP_EOL;
echo "Segun inventario (entradas - outflows): " . ($total - $outflowOut[0]->total) . " lts" . PHP_EOL;
echo "Segun modulo combustible (entradas - fuel_outflows): " . ($total - $fuelOut[0]->total) . " lts" . PHP_EOL;
