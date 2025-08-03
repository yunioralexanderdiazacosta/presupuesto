<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CostCenter;

class Grouping extends Model
{
    use HasFactory;

    // Campos asignables masivamente
    protected $fillable = ['name', 'costcenter_id', 'season_id', 'team_id'];

    /**
     * Relación: un agrupamiento tiene muchos centros de costo
     */
    public function costCenters()
{
    return $this->belongsToMany(CostCenter::class, 'cost_center_grouping');
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
