<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product2 extends Model
{
    use HasFactory;

      protected $table = 'products2'; // Especifica la tabla si el nombre no es plural estándar

    protected $fillable = [
        'name',
        'level3',
    ];
}
