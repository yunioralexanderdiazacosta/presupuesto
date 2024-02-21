<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'observations', 'season', 'month_id', 'team_id'];

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
