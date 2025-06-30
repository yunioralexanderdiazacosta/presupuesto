<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agrochemical extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'dose', 'observations', 'mojamiento', 'unit_id', 'unit_id_price', 'subfamily_id', 'dose_type_id', 'team_id'];

    public function items()
    {
        return $this->belongsToMany(CostCenter::class, 'agrochemical_items', 'agrochemical_id', 'cost_center_id')->withPivot('month_id');
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


    public function dosetype()
    {
        return $this->belongsTo(DoseType::class, 'dose_type_id');
    }

public function team()
    {
        return $this->belongsTo(Team::class);
    }    



}
