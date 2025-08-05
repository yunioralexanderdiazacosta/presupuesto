<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLevel extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'level2_id'];
}
