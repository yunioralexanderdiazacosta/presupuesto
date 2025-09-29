<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelOutflowCostCenter extends Model
{
    use HasFactory;

    protected $table = 'fuel_outflow_cost_center';

    protected $fillable = [
        'fuel_outflow_id',
        'cost_center_id',
    ];

    public function fuelOutflow()
    {
        return $this->belongsTo(FuelOutflow::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
