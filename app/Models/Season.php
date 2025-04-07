<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id', 'month_id', 'observations'];

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
