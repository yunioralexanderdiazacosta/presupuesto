<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationOrderCostCenter extends Model
{
    use HasFactory;

    protected $table = 'application_order_cost_center';

    protected $fillable = [
        'application_order_id',
        'cost_center_id',
    ];

    /**
     * Relación con ApplicationOrder
     */
    public function applicationOrder()
    {
        return $this->belongsTo(ApplicationOrder::class);
    }

    /**
     * Relación con CostCenter
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
