<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrrigationSector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'surface',
        'irrigation_pump_id',
        'observations',
    ];

    protected $casts = [
        'surface' => 'decimal:2',
    ];

    /**
     * Relación con IrrigationPump
     */
    public function irrigationPump()
    {
        return $this->belongsTo(IrrigationPump::class);
    }
}
