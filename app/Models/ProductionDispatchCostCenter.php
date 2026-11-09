<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Cuartel-variedad que aporta fruta a una guía de despacho, con los kg que aportó. */
class ProductionDispatchCostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_dispatch_id',
        'cost_center_variety_id',
        'kg_dispatched',
    ];

    public function productionDispatch()
    {
        return $this->belongsTo(ProductionDispatch::class);
    }

    public function costCenterVariety()
    {
        return $this->belongsTo(CostCenterVariety::class);
    }
}
