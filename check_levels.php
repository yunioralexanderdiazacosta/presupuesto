<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LEVEL1s ===\n";
$level1s = DB::table('level1s')->select('id', 'name')->orderBy('id')->get();
foreach($level1s as $l1) {
    echo "ID: {$l1->id} | Nombre: '{$l1->name}'\n";
}

echo "\n=== LEVEL2s ===\n";
$level2s = DB::table('level2s')->select('id', 'name', 'level1_id')->orderBy('level1_id')->orderBy('id')->get();
foreach($level2s as $l2) {
    echo "ID: {$l2->id} (Level1: {$l2->level1_id}) | Nombre: '{$l2->name}'\n";
}
