<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OvertimeType extends Model
{
    protected $fillable = [
        'name',
        'hourly_rate_factor',
        'overtime_multiplier',
        'description',
        'active',
    ];

    protected $casts = [
        'hourly_rate_factor' => 'float',
        'overtime_multiplier' => 'float',
        'active' => 'boolean',
    ];

    public function overtimeHours(): HasMany
    {
        return $this->hasMany(OvertimeHour::class);
    }
}
