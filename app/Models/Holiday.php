<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'team_id',
        'date',
        'name',
        'is_recurring',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /** Scope: feriados nacionales (sin equipo) */
    public function scopeNational($query)
    {
        return $query->whereNull('team_id');
    }

    /** Scope: feriados de un equipo específico */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
