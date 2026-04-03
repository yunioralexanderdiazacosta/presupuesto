<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusType extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'default_amount',
        'is_active',
    ];

    protected $casts = [
        'default_amount' => 'integer',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
