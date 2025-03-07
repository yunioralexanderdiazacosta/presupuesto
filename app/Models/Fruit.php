<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fruit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id'];

    public function varieties()
    {
        return $this->hasMany(Variety::class);
    }
}
