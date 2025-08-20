<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'observations',
        'responsible',
        'date',
    ];

    // Puedes agregar relaciones si lo necesitas en el futuro
}
