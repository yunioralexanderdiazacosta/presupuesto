<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TODOS LOS USERS ===" . PHP_EOL;
$users = DB::select('SELECT id, name, email, team_id FROM users');
foreach ($users as $u) {
    $tid = $u->team_id ?? 'NULL';
    echo "User {$u->id}: {$u->name} | email: {$u->email} | team_id: {$tid}" . PHP_EOL;
}
