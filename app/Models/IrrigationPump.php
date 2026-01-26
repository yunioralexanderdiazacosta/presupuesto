<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrrigationPump extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'brand',
        'model',
        'team_id',
        'season_id',
    ];

    /**
     * Relación con Team
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación con Season
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Relación con IrrigationSectors
     */
    public function sectors()
    {
        return $this->hasMany(IrrigationSector::class);
    }
}
