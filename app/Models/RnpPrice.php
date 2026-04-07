<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RnpPrice extends Model
{
    protected $fillable = ['team_id', 'project_evaluation_id', 'variety_id', 'week', 'price_usd'];

    public function projectEvaluation()
    {
        return $this->belongsTo(ProjectEvaluation::class);
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
