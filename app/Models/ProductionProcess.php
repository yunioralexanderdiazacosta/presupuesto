<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Proceso del packing informado una sola vez. Puede incluir lotes de distintas guías;
 * cada lote recibe su parte prorrateada en ProductionProcessLot y en sus ítems de clasificación.
 */
class ProductionProcess extends Model
{
    protected $fillable = [
        'team_id',
        'season_id',
        'process_number',
        'process_date',
        'kg_to_process',
        'kg_exported',
        'kg_national',
        'kg_industrial',
        'kg_waste',
    ];

    protected $casts = [
        'process_date' => 'date:Y-m-d',
    ];

    public function allocations()
    {
        return $this->hasMany(ProductionProcessLot::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionDispatchItem::class);
    }
}
