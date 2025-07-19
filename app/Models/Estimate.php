<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    protected $fillable = [
        'estimate_name',
        'kilos_ha',
        'cost_center_id',
        'observations',
        'season_id',
        'team_id',
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
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
