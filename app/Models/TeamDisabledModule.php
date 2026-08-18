<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamDisabledModule extends Model
{
    protected $fillable = ['team_id', 'module_key'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
