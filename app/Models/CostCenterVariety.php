<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCenterVariety extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_center_id',
        'season_id',
        'variety_id',
        'fruit_id',
        'rootstock_id',
        'development_state_id',
        'surface',
        'year_plantation',
        'observations',
        'team_id',
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function fruit()
    {
        return $this->belongsTo(Fruit::class);
    }

    public function rootstock()
    {
        return $this->belongsTo(Rootstock::class);
    }

    public function developmentState()
    {
        return $this->belongsTo(DevelopmentState::class);
    }
}
