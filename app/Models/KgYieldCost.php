<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KgYieldCost extends Model
{
    protected $fillable = ['team_id', 'project_evaluation_id', 'kg_ha', 'cost_usd'];

    public function projectEvaluation()
    {
        return $this->belongsTo(ProjectEvaluation::class);
    }
}
