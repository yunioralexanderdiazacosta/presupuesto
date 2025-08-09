<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPower extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'workday', 'observations', 'subfamily_id', 'unit_id', 'team_id', 'season_id', 'user_id'];

    public function items()
    {
        return $this->belongsToMany(CostCenter::class, 'manpower_items', 'man_power_id', 'cost_center_id')->withPivot('month_id');
    }

    public function subfamily()
    {
          return $this->belongsTo(Level3::class, 'subfamily_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    } 

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
