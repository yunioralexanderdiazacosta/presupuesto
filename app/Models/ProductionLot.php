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

    /** Partes de procesos en los que participó el lote (un lote puede estar en varios). */
    public function allocations()
    {
        return $this->hasMany(ProductionProcessLot::class);
    }

    /**
     * Los datos de proceso del lote son la suma de lo que le correspondió en cada proceso.
     * Con al menos un proceso queda "processed"; el saldo sin procesar es kg_received - kg_to_process.
     */
    public function syncFromProcesses(): void
    {
        $allocations = $this->allocations()->with('process')->get();

        if ($allocations->isEmpty()) {
            $this->update([
                'status' => 'received',
                'process_date' => null,
                'kg_to_process' => null,
                'kg_exported' => null,
                'kg_national' => null,
                'kg_industrial' => null,
                'kg_waste' => null,
            ]);
            return;
        }

        $sum = fn(string $field) => round((float) $allocations->sum($field), 2);

        $this->update([
            'status' => 'processed',
            'process_date' => $allocations->map(fn($a) => $a->process?->process_date)->filter()->max(),
            'kg_to_process' => $sum('kg_to_process'),
            'kg_exported' => $sum('kg_exported'),
            'kg_national' => $sum('kg_national'),
            'kg_industrial' => $sum('kg_industrial'),
            'kg_waste' => $sum('kg_waste'),
        ]);
    }
}
