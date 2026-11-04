<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id', 'month_id', 'observations', 'is_default', 'color', 'is_locked'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_locked'  => 'boolean',
    ];

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function budgetVersions()
    {
        return $this->hasMany(BudgetVersion::class);
    }

    public function officialBudgetVersion()
    {
        return $this->hasOne(BudgetVersion::class)->where('is_official', true);
    }
}
