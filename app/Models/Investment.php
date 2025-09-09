<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'month_execute',
    'amount',
    'estado',
    'responsable',
    'season_id',
    'observations',
    ];

    // Relación muchos a muchos con cost centers
    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'cost_center_investment');
    }


    // Relación con temporada
    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
