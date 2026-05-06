<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceProduct extends Model
{
    use HasFactory;

    protected $table = 'invoice_products';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_price',
        'original_unit_price',
        'amount',
        'observations',
        'is_exento',
        'branch_id',
        'tank_id',
    ];

    protected $casts = [
        'is_exento' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tank()
    {
        return $this->belongsTo(FuelTank::class, 'tank_id');
    }
    // Relación a unidad
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
