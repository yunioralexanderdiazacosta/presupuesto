<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetVersion extends Model
{
    use HasFactory;

    protected $fillable = ['season_id', 'team_id', 'name', 'observations', 'is_official', 'is_locked', 'created_by'];

    protected $casts = [
        'is_official' => 'boolean',
        'is_locked'   => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }
}
