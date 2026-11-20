<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Parte de un proceso que le corresponde a un lote. */
class ProductionProcessLot extends Model
{
    protected $fillable = [
        'production_process_id',
        'production_lot_id',
        'kg_to_process',
        'kg_exported',
        'kg_national',
        'kg_industrial',
        'kg_waste',
    ];

    public function process()
    {
        return $this->belongsTo(ProductionProcess::class, 'production_process_id');
    }

    public function lot()
    {
        return $this->belongsTo(ProductionLot::class, 'production_lot_id');
    }
}
