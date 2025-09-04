<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level3Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'level2_template_id',
        'name',
        'order',
    ];

    public function level2Template()
    {
        return $this->belongsTo(Level2Template::class);
    }
}
