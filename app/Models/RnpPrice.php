<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RnpPrice extends Model
{
    protected $fillable = ['team_id', 'variety_id', 'week', 'price_usd'];

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
