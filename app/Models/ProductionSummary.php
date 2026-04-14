<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSummary extends Model
{
    protected $fillable = [
        'variety_id',
        'season_id',
        'team_id',
        'kg_harvested',
        'kg_exported',
        'net_kilo',
        'commercial_cost_per_kg',
        'observations',
    ];

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
