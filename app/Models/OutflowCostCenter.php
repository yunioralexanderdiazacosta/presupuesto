<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutflowCostCenter extends Model
{
    use HasFactory;

    protected $table = 'outflow_cost_center';

    protected $fillable = [
        'outflow_id',
        'cost_center_id',
        'observations',
    ];

    // Relaciones
    public function outflow()
    {
        return $this->belongsTo(Outflow::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
