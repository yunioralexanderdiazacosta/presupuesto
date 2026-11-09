<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lote asignado por el packing al recepcionar fruta de una guía de despacho.
 * Una guía puede tener uno o más lotes; cada lote se procesa por separado.
 */
class ProductionLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_dispatch_id',
        'team_id',
        'season_id',
        // Recepción
        'lot_number',
        'reception_date',
        'kg_received',
        'observations',
        'status',
        // Proceso
        'process_date',
        'kg_to_process',
        'kg_exported',
        'kg_national',
        'kg_industrial',
        'kg_waste',
    ];

    protected $casts = [
        'reception_date' => 'date:Y-m-d',
        'process_date' => 'date:Y-m-d',
    ];

    public function productionDispatch()
    {
        return $this->belongsTo(ProductionDispatch::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionDispatchItem::class);
    }
}
