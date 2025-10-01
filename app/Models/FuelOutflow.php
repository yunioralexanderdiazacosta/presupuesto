<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelOutflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'machinery_id',
        'operator_id',
        'cost_center_id',
    // ...existing code...
        'fuel_type',
        'liters',
        'horometer',
        'odometer',
        'date',
        'observations',
    ];
    // ...existing code...

    // Relaciones
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    // Relación igual que en Outflow: obtener los pivotes
    public function costCenters()
    {
        return $this->hasMany(FuelOutflowCostCenter::class);
    }
}
