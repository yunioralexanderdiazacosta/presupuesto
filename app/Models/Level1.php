<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level1 extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id', 'season_id'];

    public function levels2()
    {
        return $this->hasMany(Level2::class, 'level1_id');
    }
}
