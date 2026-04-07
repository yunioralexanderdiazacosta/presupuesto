<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarietyCostParam extends Model
{
    protected $fillable = ['team_id', 'project_evaluation_id', 'variety_id', 'pct_embalaje', 'precio_proceso'];

    public function projectEvaluation()
    {
        return $this->belongsTo(ProjectEvaluation::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
