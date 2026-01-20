<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhenologicalStage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fruit_id', 'team_id'];

    public function fruit()
    {
        return $this->belongsTo(Fruit::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
