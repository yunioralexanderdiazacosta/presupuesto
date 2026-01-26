<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FertilizerOrderIrrigationSector extends Model
{
    use HasFactory;

    protected $table = 'fertilizer_order_irrigation_sector';

    protected $fillable = [
        'fertilizer_order_id',
        'irrigation_sector_id',
        'surface',
    ];

    protected $casts = [
        'surface' => 'decimal:2',
    ];

    public function fertilizerOrder()
    {
        return $this->belongsTo(FertilizerOrder::class);
    }

    public function irrigationSector()
    {
        return $this->belongsTo(IrrigationSector::class);
    }
}
