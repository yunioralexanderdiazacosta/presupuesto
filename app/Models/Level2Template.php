<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level2Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'level1_template_id',
        'name',
        'order',
    ];

    public function level1Template()
    {
        return $this->belongsTo(Level1Template::class);
    }
}
