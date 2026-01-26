<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FertilizerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'irrigation_pump_id',
        'responsable',
        'observations',
        'status',
        'team_id',
        'season_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function irrigationPump()
    {
        return $this->belongsTo(IrrigationPump::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'fertilizer_order_product')
            ->withPivot(['dosis_por_hectarea', 'cantidad_total', 'unit_id'])
            ->withTimestamps();
    }

    public function orderProducts()
    {
        return $this->hasMany(FertilizerOrderProduct::class);
    }

    public function irrigationSectors()
    {
        return $this->belongsToMany(IrrigationSector::class, 'fertilizer_order_irrigation_sector')
            ->withPivot('surface')
            ->withTimestamps();
    }

    public function orderIrrigationSectors()
    {
        return $this->hasMany(FertilizerOrderIrrigationSector::class);
    }

    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'fertilizer_order_cost_center')
            ->withTimestamps();
    }

    public function orderCostCenters()
    {
        return $this->hasMany(FertilizerOrderCostCenter::class);
    }
}
