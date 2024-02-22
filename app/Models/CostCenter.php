<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'surface', 'budget_id'];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function agrochemicals()
    {
        return $this->belongsToMany(Agrochemicals::class, 'agrochemical_items', 'cost_center_id', 'agrochemical_id');
    }    
}
