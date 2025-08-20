<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_center_id',
        'date',
        'user_id',
        'team_id',
        'season_id',
        'operation_id',
        'machinary_id',
        'project_id',
        'observations',
    ];

    public function items()
    {
        return $this->hasMany(ConsumptionItem::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
