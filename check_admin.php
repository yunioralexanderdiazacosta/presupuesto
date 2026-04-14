<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$season_id = DB::table('seasons')->orderByDesc('id')->value('id');
// Find a user with a team_id
$user = DB::table('users')->whereNotNull('team_id')->first();
$team_id = $user->team_id;
echo "User: {$user->id} ({$user->name}), Season: $season_id, Team: $team_id\n";

// Admin dev state IDs
$adminIds = DB::table('development_states')
    ->whereRaw("LOWER(REPLACE(name, 'ó', 'o')) LIKE '%administracion%'")
    ->pluck('id', 'name');
echo "Admin dev states: " . json_encode($adminIds) . "\n";

// Superficie total por outflow
$surfaceTotals = DB::table('outflow_cost_center')
    ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
    ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
    ->groupBy('outflow_cost_center.outflow_id');

// ── Método OutflowsDashboard (nuevo) ──
$adminTotal = DB::table('outflows as o')
    ->join('outflow_cost_center as occ', 'o.id', '=', 'occ.outflow_id')
    ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
    ->leftJoin('operations as op', 'o.operation_id', '=', 'op.id')
    ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
    ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
    ->leftJoinSub($surfaceTotals, 'st', fn($j) => $j->on('o.id', '=', 'st.outflow_id'))
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->whereIn('cc.development_state_id', $adminIds->values())
    ->where(fn($q) => $q->whereNull('op.name')->orWhereRaw('LOWER(op.name) NOT LIKE ?', ['%inversion%']))
    ->selectRaw("COALESCE(SUM(CASE WHEN cc.surface = 0 THEN o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0) ELSE (cc.surface * (o.quantity / NULLIF(st.total_surface, 0))) * COALESCE(ip.unit_price, cdni.unit_price, 0) END), 0) as total")
    ->value('total');
echo "Admin total (OutflowsDashboard style): " . number_format($adminTotal, 0, ',', '.') . " CLP\n";

// ── Método viejo ProfitLoss (CCs sin CCVs) ──
$ccIdsWithCCV = DB::table('cost_center_varieties')
    ->where('season_id', $season_id)
    ->where('team_id', $team_id)
    ->distinct()
    ->pluck('cost_center_id');

$oldAdmin = DB::table('outflows as o')
    ->join('outflow_cost_center as occ', 'o.id', '=', 'occ.outflow_id')
    ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
    ->leftJoin('operations as op', 'o.operation_id', '=', 'op.id')
    ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
    ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
    ->leftJoinSub($surfaceTotals, 'st', fn($j) => $j->on('o.id', '=', 'st.outflow_id'))
    ->where('o.season_id', $season_id)
    ->where('o.team_id', $team_id)
    ->whereNull('o.fuel_outflow_id')
    ->whereNotIn('cc.id', $ccIdsWithCCV)
    ->whereNotNull('cc.development_state_id')
    ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(st.total_surface,0) > 0 THEN (cc.surface / st.total_surface) * o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0) ELSE o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0) END), 0) as total")
    ->value('total');
echo "Admin total (old ProfitLoss style): " . number_format($oldAdmin, 0, ',', '.') . " CLP\n";

// Diferencia
echo "Diferencia: " . number_format($adminTotal - $oldAdmin, 0, ',', '.') . " CLP\n";

// ── Precio dolar ──
$dollarPrice = DB::table('users')->where('team_id', $team_id)->whereNotNull('dollar_price')->value('dollar_price') ?? 970;
echo "Dollar price: $dollarPrice\n";
echo "Admin USD (nuevo): " . number_format($adminTotal / $dollarPrice, 0, ',', '.') . "\n";
echo "Admin USD (viejo): " . number_format($oldAdmin / $dollarPrice, 0, ',', '.') . "\n";

// Check data counts
echo "\n--- DATA COUNTS ---\n";
echo "Outflows: " . DB::table('outflows')->count() . "\n";
echo "CCs: " . DB::table('cost_centers')->count() . "\n";
echo "OCC: " . DB::table('outflow_cost_center')->count() . "\n";
echo "CCVs: " . DB::table('cost_center_varieties')->count() . "\n";
echo "CCs with dev_state admin: " . DB::table('cost_centers')->whereIn('development_state_id', $adminIds->values())->count() . "\n";
echo "Outflows with admin CCs: " . DB::table('outflow_cost_center')->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')->whereIn('cost_centers.development_state_id', $adminIds->values())->count() . "\n";

// Check seasons
echo "\n--- OUTFLOWS BY SEASON ---\n";
$byS = DB::table('outflows')->select('season_id', DB::raw('count(*) as cnt'))->groupBy('season_id')->get();
foreach($byS as $s) echo "Season $s->season_id: $s->cnt outflows\n";

// Check seasons with admin outflows  
echo "\n--- ADMIN OUTFLOWS BY SEASON/TEAM ---\n";
$adminOutflows = DB::table('outflows as o')
    ->join('outflow_cost_center as occ', 'o.id', '=', 'occ.outflow_id')
    ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
    ->whereIn('cc.development_state_id', $adminIds->values())
    ->select('o.season_id', 'o.team_id', DB::raw('count(DISTINCT o.id) as cnt'))
    ->groupBy('o.season_id', 'o.team_id')
    ->get();
foreach($adminOutflows as $a) echo "Season: $a->season_id, Team: $a->team_id, Count: $a->cnt\n";
