<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level2 extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'level1_id'];

    public function level3s()
    {
        return $this->hasMany(Level3::class, 'level2_id');
    }
}
