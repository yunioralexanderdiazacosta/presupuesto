<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$notes = App\Models\CreditDebitNote::where('team_id', 1)
    ->where('season_id', 4)
    ->get(['id','type','number','affects_inventory','invoice_id']);

echo "=== NCs/NDs del team 1, season 4 ===" . PHP_EOL;
foreach($notes as $n) {
    $tipo = $n->type;
    $es_credito = false;
    if (in_array($tipo, ['ND', 'debito'])) {
        $tipo = 'Débito';
    } elseif (in_array($tipo, ['NC', 'credito'])) {
        $tipo = 'Crédito';
        $es_credito = true;
    }
    $isFinancial = !$n->affects_inventory && $es_credito;
    echo "ID={$n->id} type={$n->type} normalized={$tipo} es_credito=" . ($es_credito ? 'YES' : 'no') 
         . " affects_inv={$n->affects_inventory} is_financial=" . ($isFinancial ? 'YES' : 'no') . PHP_EOL;
}
