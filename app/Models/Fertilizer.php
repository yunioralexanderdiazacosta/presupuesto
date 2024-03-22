<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fertilizer extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'dose', 'unit_id', 'unit_id_price', 'subfamily_id', 'observations'];

    public function items()
    {
        return $this->belongsToMany(CostCenter::class, 'fertilizer_items', 'fertilizer_id', 'cost_center_id')->withPivot('month_id');
    }

    public function subfamily()
    {
        return $this->belongsTo(Subfamily::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
