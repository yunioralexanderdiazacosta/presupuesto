<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KgYieldCost extends Model
{
    protected $fillable = ['team_id', 'kg_ha', 'cost_usd'];
}
