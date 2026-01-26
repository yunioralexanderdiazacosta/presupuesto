<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FertilizerOrderCostCenter extends Model
{
    use HasFactory;

    protected $table = 'fertilizer_order_cost_center';

    protected $fillable = [
        'fertilizer_order_id',
        'cost_center_id',
    ];

    public function fertilizerOrder()
    {
        return $this->belongsTo(FertilizerOrder::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
