<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionAdvance extends Model
{
    protected $fillable = ['production_id', 'type', 'name', 'amount'];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
