<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateStatus extends Model
{
    protected $table = 'estimate_status';

    protected $fillable = [
        'name',
        'fruit_id',
        'observations',
    ];

    /**
     * Get the fruit associated with the estimate status.
     */
    public function fruit(): BelongsTo
    {
        return $this->belongsTo(Fruit::class, 'fruit_id');
    }
}
