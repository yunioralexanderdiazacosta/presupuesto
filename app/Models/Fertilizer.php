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
        return $this->belongsTo(Level3::class, 'subfamily_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function unit2()
    {
        return $this->belongsTo(Unit::class, 'unit_id_price');
    }
}
