<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }
}
