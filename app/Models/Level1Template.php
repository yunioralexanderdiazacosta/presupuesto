<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level1Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    public function level2Templates()
    {
        return $this->hasMany(Level2Template::class);
    }
}
