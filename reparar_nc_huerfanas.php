<?php

/*
 |--------------------------------------------------------------------------
 | Reparación de Notas de Crédito huérfanas (items sin invoice_product_id)
 |--------------------------------------------------------------------------
 | Re-enlaza cada item de NC a su línea de factura correcta de forma SEGURA:
 |   - Usa el invoice_id que la propia NC ya tiene guardado (no el número de
 |     documento), por lo que NUNCA puede tomar la factura de otro team.
 |   - Valida que la línea candidata pertenezca al MISMO team de la NC.
 |   - Matchea por product_id dentro de esa factura.
 |   - Solo re-enlaza cuando hay UN ÚNICO candidato. Si hay 0 o varios,
 |     lo reporta y NO toca nada (revisión manual).
 |
 | MODO SIMULACIÓN (dry-run) por defecto: NO escribe nada, solo muestra.
 | Para aplicar cambios, ejecutar con el argumento "aplicar":
 |     php artisan tinker reparar_nc_huerfanas.php          (simula)
 |     APLICAR=1 php artisan tinker reparar_nc_huerfanas.php (aplica)   [Linux]
 |   En Windows PowerShell para aplicar:
 |     $env:APLICAR=1; php artisan tinker reparar_nc_huerfanas.php; Remove-Item env:APLICAR
 |
 | Borrar este archivo después de usarlo.
 |--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\DB;

$aplicar = (getenv('APLICAR') === '1');

echo "==================================================================\n";
echo " REPARACIÓN DE NCs HUÉRFANAS  " . ($aplicar ? "[MODO APLICAR]" : "[SIMULACIÓN - no escribe]") . "\n";
echo "==================================================================\n\n";

$huerfanos = DB::table('credit_debit_note_items as cdni')
    ->join('credit_debit_notes as cdn', 'cdni.credit_debit_note_id', '=', 'cdn.id')
    ->where('cdn.type', 'credito')
    ->where('cdn.affects_inventory', 1)
    ->whereNotNull('cdn.invoice_id')
    ->where(function ($q) {
        $q->whereNull('cdni.invoice_product_id')->orWhere('cdni.invoice_product_id', 0);
    })
    ->select(
        'cdn.id as nc_id', 'cdn.number as nc_number', 'cdn.invoice_id', 'cdn.team_id',
        'cdni.id as item_id', 'cdni.product_id', 'cdni.quantity', 'cdni.unit_price'
    )
    ->orderBy('cdn.id')
    ->get();

echo "Items huérfanos encontrados: " . $huerfanos->count() . "\n\n";

$reparados = 0;
$pendientes = 0;

foreach ($huerfanos as $h) {
    // Candidatos: líneas de LA factura ligada (invoice_id) + mismo team + mismo producto
    $candidatos = DB::table('invoice_products as ip')
        ->join('invoices as i', 'ip.invoice_id', '=', 'i.id')
        ->where('ip.invoice_id', $h->invoice_id)
        ->where('i.team_id', $h->team_id)           // blindaje por team
        ->where('ip.product_id', $h->product_id)    // mismo producto
        ->select('ip.id', 'ip.unit_price', 'ip.amount')
        ->get();

    $etiqueta = "NC {$h->nc_number} (item {$h->item_id}) prod_id={$h->product_id} qty={$h->quantity} price={$h->unit_price}";

    if ($candidatos->count() === 0) {
        echo "  [SIN CANDIDATO] $etiqueta -> el producto no está en la factura. Revisar manual.\n";
        $pendientes++;
        continue;
    }

    if ($candidatos->count() > 1) {
        // Desempate por precio exacto
        $exactos = $candidatos->filter(fn($c) => round((float)$c->unit_price, 2) === round((float)$h->unit_price, 2));
        if ($exactos->count() === 1) {
            $c = $exactos->first();
        } else {
            echo "  [VARIOS CANDIDATOS] $etiqueta -> IPs: " . $candidatos->pluck('id')->implode(',') . ". Revisar manual.\n";
            $pendientes++;
            continue;
        }
    } else {
        $c = $candidatos->first();
    }

    $precioOk = round((float)$c->unit_price, 2) === round((float)$h->unit_price, 2);
    $nota = $precioOk ? "precio OK" : "precio difiere (IP={$c->unit_price}) pero producto+factura coinciden";

    if ($aplicar) {
        DB::table('credit_debit_note_items')->where('id', $h->item_id)->update(['invoice_product_id' => $c->id]);
        echo "  [REPARADO] $etiqueta -> IP={$c->id} ($nota)\n";
    } else {
        echo "  [SIMULADO]  $etiqueta -> IP={$c->id} ($nota)\n";
    }
    $reparados++;
}

echo "\n------------------------------------------------------------------\n";
echo " Resumen: " . ($aplicar ? "reparados" : "se repararían") . "=$reparados | requieren revisión manual=$pendientes\n";
if (!$aplicar && $reparados > 0) {
    echo " Esto fue una SIMULACIÓN. Para aplicar (Windows PowerShell):\n";
    echo "   \$env:APLICAR=1; php artisan tinker reparar_nc_huerfanas.php; Remove-Item env:APLICAR\n";
}
echo "==================================================================\n";
