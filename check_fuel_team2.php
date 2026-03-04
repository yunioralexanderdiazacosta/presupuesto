<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$teamId = 2;
$seasonId = DB::table('seasons')->where('team_id', $teamId)->orderBy('id', 'desc')->value('id');

echo "Team: $teamId | Season: $seasonId" . PHP_EOL . PHP_EOL;

// Todas las líneas de factura de combustible
$rows = DB::select("
    SELECT ip.id as ip_id, ip.invoice_id, ip.product_id, p.name as producto, 
           ip.amount, ip.unit_price, i.number_document, i.season_id
    FROM invoice_products ip
    JOIN products p ON ip.product_id = p.id
    JOIN level3s l3 ON p.level3_id = l3.id
    JOIN invoices i ON ip.invoice_id = i.id
    WHERE LOWER(l3.name) LIKE '%combustible%'
    AND i.team_id = ?
    ORDER BY i.id, ip.id
", [$teamId]);

echo "=== TODAS LAS LINEAS DE FACTURA DE COMBUSTIBLE (team $teamId) ===" . PHP_EOL;
echo str_pad('IP_ID', 8) . str_pad('FACTURA', 12) . str_pad('SEASON', 8) . str_pad('PRODUCTO', 25) . str_pad('CANTIDAD', 12) . 'PRECIO' . PHP_EOL;
echo str_repeat('-', 80) . PHP_EOL;

$total = 0;
$totalBySeason = [];
$duplicates = [];
$invoiceProducts = [];

foreach ($rows as $r) {
    echo str_pad($r->ip_id, 8) . str_pad($r->number_document, 12) . str_pad($r->season_id, 8) . str_pad($r->producto, 25) . str_pad($r->amount, 12) . $r->unit_price . PHP_EOL;
    $total += $r->amount;
    $totalBySeason[$r->season_id] = ($totalBySeason[$r->season_id] ?? 0) + $r->amount;
    
    $key = $r->invoice_id . '-' . $r->product_id;
    if (isset($invoiceProducts[$key])) {
        $duplicates[] = ['invoice_id' => $r->invoice_id, 'number' => $r->number_document, 'product' => $r->producto, 'ip_id' => $r->ip_id, 'amount' => $r->amount];
    }
    $invoiceProducts[$key] = $r;
}

echo PHP_EOL . "Total registros: " . count($rows) . PHP_EOL;
echo "Total litros GLOBAL: $total" . PHP_EOL;
foreach ($totalBySeason as $sid => $t) {
    echo "  Season $sid: $t lts" . PHP_EOL;
}

if (!empty($duplicates)) {
    echo PHP_EOL . "=== DUPLICADOS (mismo producto en misma factura) ===" . PHP_EOL;
    foreach ($duplicates as $d) {
        echo "  Factura #{$d['number']} (inv_id={$d['invoice_id']}) → {$d['product']} ip_id={$d['ip_id']} amount={$d['amount']}" . PHP_EOL;
    }
} else {
    echo PHP_EOL . "No hay duplicados (mismo producto en misma factura)" . PHP_EOL;
}

// Ahora veamos qué retorna belongsToMany vs invoiceProducts para el team
echo PHP_EOL . "=== COMPARACION belongsToMany vs hasMany ===" . PHP_EOL;

$invoices = \App\Models\Invoice::with(['products', 'invoiceProducts.product'])
    ->where('team_id', $teamId)
    ->whereHas('invoiceProducts.product', function($q) {
        $q->whereHas('level3', function($q2) {
            $q2->where('name', 'like', '%combustible%');
        });
    })
    ->get();

$totalBTM = 0;
$totalHM = 0;

foreach ($invoices as $inv) {
    $btmCount = 0;
    $hmCount = 0;
    
    foreach ($inv->products as $p) {
        $l3 = DB::table('level3s')->where('id', $p->level3_id)->value('name');
        if (stripos($l3, 'combustible') !== false) {
            $btmCount++;
            $totalBTM += $p->pivot->amount;
        }
    }
    
    foreach ($inv->invoiceProducts as $ip) {
        if (!$ip->product) continue;
        $l3 = DB::table('level3s')->where('id', $ip->product->level3_id)->value('name');
        if (stripos($l3, 'combustible') !== false) {
            $hmCount++;
            $totalHM += $ip->amount;
        }
    }
    
    if ($btmCount != $hmCount) {
        echo "  ⚠️ Factura #{$inv->number_document} (id={$inv->id}): belongsToMany=$btmCount lineas, hasMany=$hmCount lineas" . PHP_EOL;
    }
}

echo PHP_EOL . "Total lts via belongsToMany (products): $totalBTM" . PHP_EOL;
echo "Total lts via hasMany (invoiceProducts): $totalHM" . PHP_EOL;
echo "Diferencia: " . ($totalHM - $totalBTM) . " lts" . PHP_EOL;
