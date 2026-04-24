<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationEntitlement extends Model
{
    protected $fillable = [
        'employee_id',
        'anos_anteriores',
        'observacion',
    ];

    protected $casts = [
        'anos_anteriores' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
