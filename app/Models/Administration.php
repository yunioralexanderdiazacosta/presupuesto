<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administration extends Model
{
    use HasFactory;

protected $fillable = ['product_name', 'price', 'observations', 'quantity', 'unit_id','subfamily_id', 'team_id', 'season_id'];

    public function items()
    {
        return $this->hasMany(AdministrationItem::class, 'administration_id');
    }

    public function subfamily()
    {
        return $this->belongsTo(Level3::class, 'subfamily_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

       public function season()
{
    return $this->belongsTo(Season::class);
}

  public function team()
    {
        return $this->belongsTo(Team::class);
    }    

}
