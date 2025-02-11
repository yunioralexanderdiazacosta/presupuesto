<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fruit_id', 'team_id', 'observations'];

    public function fruit()
    {
        return $this->belongsTo(Fruit::class, 'fruit_id');
    }
}
