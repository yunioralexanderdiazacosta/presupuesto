<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'surface', 'season_id', 'observations', 'fruit_id', 'variety_id', 'parcel_id', 'year_plantation', 'development_state_id','company_reason_id', 'status'];

    protected $casts = [ 'status' => 'boolean'];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function agrochemicals()
    {
        return $this->belongsToMany(Agrochemical::class, 'agrochemical_items', 'cost_center_id', 'agrochemical_id');
    }

    public function fertilizers()
    {
         return $this->belongsToMany(Fertilizer::class, 'fertilizer_items', 'cost_center_id', 'fertilizer_id');
    }   
    
    public function fruit()
    {
        return $this->belongsTo(fruit::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function developmentState()
    {
        return $this->belongsTo(DevelopmentState::class);
    }

 public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }
public function grouping()
{
    return $this->belongsTo(Grouping::class);
}
}