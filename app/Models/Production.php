<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'season_id',
        'team_id',
        'fruit_id',
        'notes',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function fruit()
    {
        return $this->belongsTo(Fruit::class);
    }

    public function summaries()
    {
        return $this->hasMany(ProductionSummary::class);
    }

    public function advances()
    {
        return $this->hasMany(ProductionAdvance::class)->orderBy('created_at');
    }
}
