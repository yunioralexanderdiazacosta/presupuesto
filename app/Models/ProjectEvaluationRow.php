<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectEvaluationRow extends Model
{
    protected $fillable = [
        'project_evaluation_id', 'variety_id', 'week',
        'hectares', 'kg_pessimistic', 'kg_base', 'kg_optimistic',
    ];

    public function evaluation()
    {
        return $this->belongsTo(ProjectEvaluation::class, 'project_evaluation_id');
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
