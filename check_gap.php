<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$team_id = 1;
$season_id = 4;

// Comparar monto factura vs monto NC para facturas que tienen NC
echo "=== COMPARACION: Monto Factura vs Monto NC (facturas con NC) ===\n";
$comparacion = DB::select("
    SELECT 
        i.id as invoice_id,
        i.number_document,
        COALESCE(s.name, 'N/A') as supplier_name,
        ROUND(SUM(ip.unit_price * ip.amount)) as monto_factura,
        (
            SELECT COALESCE(ROUND(SUM(cdni2.unit_price * cdni2.quantity)), 0)
            FROM credit_debit_notes cdn2
            JOIN credit_debit_note_items cdni2 ON cdn2.id = cdni2.credit_debit_note_id
            WHERE cdn2.invoice_id = i.id
            AND LOWER(cdn2.type) IN ('credito', 'nc')
        ) as monto_nc,
        COALESCE(ROUND(SUM(o_sub.quantity * ip.unit_price)), 0) as monto_consumido
    FROM invoices i
    JOIN invoice_products ip ON i.id = ip.invoice_id
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    LEFT JOIN outflows o_sub ON o_sub.invoice_product_id = ip.id
    WHERE i.team_id = ?
      AND i.season_id = ?
      AND i.id IN (
          SELECT cdn3.invoice_id FROM credit_debit_notes cdn3 
          WHERE cdn3.invoice_id IS NOT NULL
          AND cdn3.team_id = ?
          AND cdn3.season_id = ?
          AND LOWER(cdn3.type) IN ('credito', 'nc')
      )
    GROUP BY i.id, i.number_document, s.name
    ORDER BY i.number_document
", [$team_id, $season_id, $team_id, $season_id]);

$totalFactura = 0;
$totalNC = 0;
$totalConsumo = 0;
foreach ($comparacion as $c) {
    $diff = $c->monto_factura - $c->monto_nc - $c->monto_consumido;
    $totalFactura += $c->monto_factura;
    $totalNC += $c->monto_nc;
    $totalConsumo += $c->monto_consumido;
    
    if ($diff != 0) {
        echo sprintf("Fact #%s | %s | Factura: $%s | NC: $%s | Consumido: $%s | GAP: $%s\n",
            $c->number_document, $c->supplier_name,
            number_format($c->monto_factura, 0, ',', '.'),
            number_format($c->monto_nc, 0, ',', '.'),
            number_format($c->monto_consumido, 0, ',', '.'),
            number_format($diff, 0, ',', '.'));
    }
}
echo "\nFacturas con NC - Totales:\n";
echo "  Monto Facturas: $" . number_format($totalFactura, 0, ',', '.') . "\n";
echo "  Monto NC:       $" . number_format($totalNC, 0, ',', '.') . "\n";
echo "  Consumido:      $" . number_format($totalConsumo, 0, ',', '.') . "\n";
echo "  GAP total:      $" . number_format($totalFactura - $totalNC - $totalConsumo, 0, ',', '.') . "\n";

// Ahora ver factura por factura las que tienen GAP
echo "\n=== DETALLE POR PRODUCTO: Facturas con NC que tienen GAP ===\n";
foreach ($comparacion as $c) {
    $diff = $c->monto_factura - $c->monto_nc - $c->monto_consumido;
    if ($diff != 0) {
        echo "\n--- Factura #{$c->number_document} ({$c->supplier_name}) ---\n";
        
        // Productos de la factura
        $prods = DB::select("
            SELECT p.name, ip.amount, ip.unit_price, 
                   ROUND(ip.amount * ip.unit_price) as total,
                   COALESCE(SUM(o.quantity), 0) as consumed
            FROM invoice_products ip
            LEFT JOIN products p ON ip.product_id = p.id
            LEFT JOIN outflows o ON o.invoice_product_id = ip.id
            WHERE ip.invoice_id = ?
            GROUP BY ip.id, p.name, ip.amount, ip.unit_price
        ", [$c->invoice_id]);
        
        echo "  PRODUCTOS:\n";
        foreach ($prods as $p) {
            echo sprintf("    %s | Qty: %s | P.U: $%s | Total: $%s | Consumido: %s\n",
                $p->name ?? 'N/A', $p->amount, 
                number_format($p->unit_price, 0, ',', '.'),
                number_format($p->total, 0, ',', '.'),
                $p->consumed);
        }
        
        // NCs de esta factura
        $ncItems = DB::select("
            SELECT cdn.number, cdni.quantity, cdni.unit_price,
                   ROUND(cdni.quantity * cdni.unit_price) as total,
                   COALESCE(p.name, 'N/A') as product_name
            FROM credit_debit_notes cdn
            JOIN credit_debit_note_items cdni ON cdn.id = cdni.credit_debit_note_id
            LEFT JOIN products p ON cdni.product_id = p.id
            WHERE cdn.invoice_id = ?
            AND LOWER(cdn.type) IN ('credito', 'nc')
        ", [$c->invoice_id]);
        
        echo "  NOTAS DE CREDITO:\n";
        foreach ($ncItems as $ni) {
            echo sprintf("    NC #%s | %s | Qty: %s | P.U: $%s | Total: $%s\n",
                $ni->number, $ni->product_name, $ni->quantity,
                number_format($ni->unit_price, 0, ',', '.'),
                number_format($ni->total, 0, ',', '.'));
        }
    }
}
