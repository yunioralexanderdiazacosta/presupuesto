<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level3 extends Model
{
    use HasFactory;

     protected $fillable = ['name', 'level2_id'];

    public function level4s()
    {
        return $this->hasMany(Level4::class, 'level3_id');
    }
}
