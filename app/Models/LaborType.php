<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaborType extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'code',
        'name',
        'level3_id',
        'unit_id',
        'default_rate',
        'default_bonus',
        'is_active',
        'is_absence',
        'is_paid',
    ];

    protected $casts = [
        'code' => 'integer',
        'default_rate' => 'integer',
        'default_bonus' => 'integer',
        'is_active' => 'boolean',
        'is_absence' => 'boolean',
        'is_paid' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function level3()
    {
        return $this->belongsTo(Level3::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
