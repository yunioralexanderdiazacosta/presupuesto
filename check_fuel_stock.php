<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Salidas huérfanas (en outflows sin fuel_outflow_id)
$result = DB::select("
    SELECT COALESCE(SUM(o.quantity), 0) as total, COUNT(*) as registros
    FROM outflows o
    JOIN invoice_products ip ON o.invoice_product_id = ip.id
    JOIN products p ON ip.product_id = p.id
    JOIN level3s l3 ON p.level3_id = l3.id
    WHERE LOWER(l3.name) LIKE '%combustible%'
    AND o.fuel_outflow_id IS NULL
");
echo "=== Salidas huerfanas (outflows SIN fuel_outflow_id) ===" . PHP_EOL;
echo "Total: " . $result[0]->total . " lts en " . $result[0]->registros . " registros" . PHP_EOL;

// 2. Total salidas en outflows para combustible
$result2 = DB::select("
    SELECT COALESCE(SUM(o.quantity), 0) as total, COUNT(*) as registros
    FROM outflows o
    JOIN invoice_products ip ON o.invoice_product_id = ip.id
    JOIN products p ON ip.product_id = p.id
    JOIN level3s l3 ON p.level3_id = l3.id
    WHERE LOWER(l3.name) LIKE '%combustible%'
");
echo PHP_EOL . "=== Total salidas combustible en OUTFLOWS ===" . PHP_EOL;
echo "Total: " . $result2[0]->total . " lts en " . $result2[0]->registros . " registros" . PHP_EOL;

// 3. Total salidas en fuel_outflows
$result3 = DB::select("
    SELECT COALESCE(SUM(liters), 0) as total, COUNT(*) as registros
    FROM fuel_outflows
");
echo PHP_EOL . "=== Total salidas en FUEL_OUTFLOWS ===" . PHP_EOL;
echo "Total: " . $result3[0]->total . " lts en " . $result3[0]->registros . " registros" . PHP_EOL;

// 4. Total entradas combustible (facturas)
$result4 = DB::select("
    SELECT COALESCE(SUM(ip.amount), 0) as total, COUNT(*) as registros
    FROM invoice_products ip
    JOIN products p ON ip.product_id = p.id
    JOIN level3s l3 ON p.level3_id = l3.id
    WHERE LOWER(l3.name) LIKE '%combustible%'
");
echo PHP_EOL . "=== Total entradas combustible (invoice_products) ===" . PHP_EOL;
echo "Total: " . $result4[0]->total . " lts en " . $result4[0]->registros . " registros" . PHP_EOL;

echo PHP_EOL . "=== STOCK CALCULADO ===" . PHP_EOL;
echo "Inventario general (entradas - outflows): " . ($result4[0]->total - $result2[0]->total) . " lts" . PHP_EOL;
echo "Modulo combustible (entradas - fuel_outflows): " . ($result4[0]->total - $result3[0]->total) . " lts" . PHP_EOL;
echo "Diferencia: " . (($result4[0]->total - $result3[0]->total) - ($result4[0]->total - $result2[0]->total)) . " lts" . PHP_EOL;
