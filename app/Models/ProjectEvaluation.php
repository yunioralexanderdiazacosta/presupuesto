<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectEvaluation extends Model
{
    protected $fillable = ['team_id', 'name', 'description', 'target_margin'];

    public function rows()
    {
        return $this->hasMany(ProjectEvaluationRow::class);
    }

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
