<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'exporter_id',
        'company_reason_id',
        'packing_house_id',
        'season_id',
        'team_id',
        // Etapa 1: Despacho
        'dispatch_date',
        'guide_number',
        'kg_dispatched',
        'bin_type_id',
        'bins_quantity',
        'box_type_id',
        'boxes_quantity',
        'carrier_id',
        'driver',
        'license_plate',
        'observations',
        'status',
        // Totales calculados desde los lotes (ver syncFromLots). kg_received = kg a proceso de los lotes procesados.
        'process_date',
        'kg_received',
        'kg_exported',
        'kg_national',
        'kg_industrial',
        'kg_waste',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'process_date' => 'date',
    ];

    /** Cuarteles que aportan fruta a la guía, con los kg de cada uno. kg_dispatched de la guía es su suma. */
    public function costCenters()
    {
        return $this->hasMany(ProductionDispatchCostCenter::class);
    }

    public function exporter()
    {
        return $this->belongsTo(Exporter::class);
    }

    /** Razón social que emite la guía de despacho. */
    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function packingHouse()
    {
        return $this->belongsTo(PackingHouse::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionDispatchItem::class);
    }

    public function lots()
    {
        return $this->hasMany(ProductionLot::class);
    }

    /**
     * Recalcula estado y totales de la guía a partir de sus lotes.
     * Los análisis de producción leen estos totales, por eso se mantienen en la guía.
     * dispatched = sin lotes, received = con lotes pendientes de proceso, processed = todos procesados.
     */
    public function syncFromLots(): void
    {
        $lots = $this->lots()->get();
        $processed = $lots->where('status', 'processed');

        $status = match (true) {
            $lots->isEmpty() => 'dispatched',
            $processed->count() === $lots->count() => 'processed',
            default => 'received',
        };

        $sum = fn(string $field) => $processed->isEmpty() ? null : round((float) $processed->sum($field), 2);

        $this->update([
            'status' => $status,
            'process_date' => $processed->max('process_date'),
            'kg_received' => $sum('kg_to_process'),
            'kg_exported' => $sum('kg_exported'),
            'kg_national' => $sum('kg_national'),
            'kg_industrial' => $sum('kg_industrial'),
            'kg_waste' => $sum('kg_waste'),
        ]);
    }

    public function binType()
    {
        return $this->belongsTo(BinType::class);
    }

    public function boxType()
    {
        return $this->belongsTo(BoxType::class);
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
}
