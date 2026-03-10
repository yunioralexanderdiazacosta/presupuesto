<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_center_variety_id',
        'exporter_id',
        'packing_house_id',
        'season_id',
        'team_id',
        // Etapa 1: Despacho
        'dispatch_date',
        'guide_number',
        'lot_number',
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
        // Etapa 2: Proceso
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

    public function costCenterVariety()
    {
        return $this->belongsTo(CostCenterVariety::class);
    }

    public function exporter()
    {
        return $this->belongsTo(Exporter::class);
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
