<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyYieldCostCenter extends Model
{
    use HasFactory;

    protected $table = 'daily_yield_cost_center';

    protected $fillable = [
        'daily_yield_id',
        'cost_center_id',
    ];

    public function dailyYield()
    {
        return $this->belongsTo(DailyYield::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
