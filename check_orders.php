<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check agrochemical outflows
$outflows = App\Models\AgrochemicalOutflow::with(['product', 'costCenter', 'applicationOrder'])
    ->orderByDesc('id')
    ->limit(20)
    ->get();

echo "=== AGROCHEMICAL OUTFLOWS ===" . PHP_EOL;
foreach ($outflows as $o) {
    echo "ID:{$o->id} | order:{$o->application_order_id} | product:{$o->product->name} | cost_center:{$o->cost_center_id} ({$o->costCenter->name}) | qty:{$o->quantity}" . PHP_EOL;
}

// Check order cost centers
$order = App\Models\ApplicationOrder::with('orderCostCenters.costCenter')->first();
if ($order) {
    echo PHP_EOL . "=== ORDER #{$order->id} COST CENTERS ===" . PHP_EOL;
    foreach ($order->orderCostCenters as $occ) {
        echo "CC:{$occ->cost_center_id} | name:{$occ->costCenter->name}" . PHP_EOL;
    }
}
