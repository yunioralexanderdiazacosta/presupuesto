<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'observations',
    ];

    // Relación: Un counter puede estar en muchas maquinarias
    public function machineries()
    {
        return $this->hasMany(Machinery::class);
    }
}
