<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OvertimeHour extends Model
{
    protected $fillable = [
        'team_id',
        'season_id',
        'contract_id',
        'month_id',
        'labor_type_id',
        'overtime_type_id',
        'hours',
        'base_salary_snapshot',
        'hourly_rate_factor_snapshot',
        'overtime_multiplier_snapshot',
        'observations',
        'user_id',
    ];

    protected $casts = [
        'hours'                       => 'float',
        'base_salary_snapshot'        => 'integer',
        'hourly_rate_factor_snapshot' => 'float',
        'overtime_multiplier_snapshot'=> 'float',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    public function laborType(): BelongsTo
    {
        return $this->belongsTo(LaborType::class);
    }

    public function overtimeType(): BelongsTo
    {
        return $this->belongsTo(OvertimeType::class);
    }

    public function costCenters(): BelongsToMany
    {
        return $this->belongsToMany(CostCenter::class, 'overtime_hour_cost_centers');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
