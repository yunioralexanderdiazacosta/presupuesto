<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level4 extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'level3_id'];
}
