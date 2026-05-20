<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSummary extends Model
{
    protected $fillable = [
        'production_id',
        'variety_id',
        'kg_harvested',
        'kg_exported',
        'net_kilo',
        'commercial_cost_per_kg',
        'observations',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
