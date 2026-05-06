<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelTank extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'branch_id',
        'product_id',
        'name',
        'capacity',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'capacity' => 'float',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class, 'tank_id');
    }

    public function fuelOutflows()
    {
        return $this->hasMany(FuelOutflow::class, 'tank_id');
    }
}
