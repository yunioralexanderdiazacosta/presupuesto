<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_id',
        'date',
        'is_present',
        'estimated_labor_type_id',
        'estimated_cost_center_id',
        'team_id',
        'season_id',
        'registered_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_present' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function estimatedLaborType()
    {
        return $this->belongsTo(LaborType::class, 'estimated_labor_type_id');
    }

    public function estimatedCostCenter()
    {
        return $this->belongsTo(CostCenter::class, 'estimated_cost_center_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
