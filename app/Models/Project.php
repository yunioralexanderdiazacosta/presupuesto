<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Operation is in the same namespace (App\Models)

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'observations',
        'budget',
        'operation_id',
        'season_id',
        'team_id',
        'user_id',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
