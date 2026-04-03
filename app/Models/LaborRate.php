<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaborRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'labor_type_id',
        'name',
        'rate',
        'unit_id',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'integer',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function laborType()
    {
        return $this->belongsTo(LaborType::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
