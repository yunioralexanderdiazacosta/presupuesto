<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDispatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_dispatch_id',
        'classification_type',
        'classification_value',
        'kg',
        'percentage',
        'boxes',
    ];

    public function productionDispatch()
    {
        return $this->belongsTo(ProductionDispatch::class);
    }
}
