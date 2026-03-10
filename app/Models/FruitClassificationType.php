<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FruitClassificationType extends Model
{
    use HasFactory;

    protected $fillable = ['fruit_id', 'type', 'value', 'sort_order', 'team_id'];

    public function fruit()
    {
        return $this->belongsTo(Fruit::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
