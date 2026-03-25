<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarietyCostParam extends Model
{
    protected $fillable = ['team_id', 'variety_id', 'pct_embalaje', 'precio_proceso'];

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
