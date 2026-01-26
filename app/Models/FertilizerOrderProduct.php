<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FertilizerOrderProduct extends Model
{
    use HasFactory;

    protected $table = 'fertilizer_order_product';

    protected $fillable = [
        'fertilizer_order_id',
        'product_id',
        'dosis_por_hectarea',
        'cantidad_total',
        'unit_id',
    ];

    protected $casts = [
        'dosis_por_hectarea' => 'decimal:2',
        'cantidad_total' => 'decimal:2',
    ];

    public function fertilizerOrder()
    {
        return $this->belongsTo(FertilizerOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
