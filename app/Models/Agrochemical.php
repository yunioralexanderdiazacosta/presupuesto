<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agrochemical extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'dose_type', 'dose', 'unit_id', 'subfamily_id', 'observations', 'mojamiento'];

    public function items()
    {
        return $this->belongsToMany(CostCenter::class, 'agrochemical_items', 'agrochemical_id', 'cost_center_id')->withPivot('month_id');
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
