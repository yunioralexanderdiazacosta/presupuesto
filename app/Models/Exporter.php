<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exporter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rut', 'contact', 'team_id'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
