<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id', 'unit_id', 'level1_id', 'level2_id', 'level3_id', 'level4_id'];
}
