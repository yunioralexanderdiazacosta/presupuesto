<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationOrderProduct extends Model
{
    use HasFactory;

    protected $table = 'application_order_product';

    protected $fillable = [
        'application_order_id',
        'product_id',
        'tipo_dosis',
        'dosis_por_100',
        'dosis_por_hectarea',
        'cantidad_por_hectarea',
        'cantidad_total',
        'carencia',
        'reingreso',
    ];

    protected $casts = [
        'dosis_por_100' => 'decimal:2',
        'dosis_por_hectarea' => 'decimal:2',
        'cantidad_por_hectarea' => 'decimal:2',
        'cantidad_total' => 'decimal:2',
        'carencia' => 'integer',
        'reingreso' => 'integer',
    ];

    /**
     * Relación con ApplicationOrder
     */
    public function applicationOrder()
    {
        return $this->belongsTo(ApplicationOrder::class);
    }

    /**
     * Relación con Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
