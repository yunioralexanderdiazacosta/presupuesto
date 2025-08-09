<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'observations', 'quantity', 'unit_id', 'unit_id_price', 'subfamily_id','team_id', 'season_id', 'user_id'];

    public function items()
    {
        return $this->belongsToMany(CostCenter::class, 'service_items', 'service_id', 'cost_center_id')->withPivot('month_id');
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
