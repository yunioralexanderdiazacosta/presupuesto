<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificationGroup extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'fruit_id', 'classification_type', 'name', 'values', 'sort_order'];

    protected $casts = [
        'values' => 'array',
    ];

    public function fruit()
    {
        return $this->belongsTo(Fruit::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
