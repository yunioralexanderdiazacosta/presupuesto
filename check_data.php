<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$teams = DB::table('cost_center_varieties')
    ->select('team_id', 'season_id', DB::raw('COUNT(*) as cnt'))
    ->groupBy('team_id', 'season_id')->get();
echo "cost_center_varieties:\n";
foreach ($teams as $t) echo "  team={$t->team_id} season={$t->season_id} count={$t->cnt}\n";

$ps = DB::table('production_summaries')
    ->select('team_id', 'season_id', DB::raw('COUNT(*) as cnt'))
    ->groupBy('team_id', 'season_id')->get();
echo "\nproduction_summaries:\n";
foreach ($ps as $p) echo "  team={$p->team_id} season={$p->season_id} count={$p->cnt}\n";

$seasons = DB::table('seasons')->select('id', 'name', 'team_id')->get();
echo "\nseasons:\n";
foreach ($seasons as $s) echo "  id={$s->id} name={$s->name} team_id={$s->team_id}\n";
