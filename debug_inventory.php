<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Traits\HasInventory;

class TestInventory {
    use HasInventory;
}

$test = new TestInventory();
$inventory = $test->getInventory(1, 4);

// Filtrar solo autoperforante
$autoPerforante = array_filter($inventory, function($item) {
    return stripos($item['product_name'], 'AUTOPERFORANTE') !== false;
});

echo "=== INVENTARIO AUTOPERFORANTE ===\n";
echo "Total filas: " . count($autoPerforante) . "\n\n";
echo json_encode(array_values($autoPerforante), JSON_PRETTY_PRINT);

