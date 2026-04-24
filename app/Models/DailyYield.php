<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyYield extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_id',
        'date',
        'payment_type',
        'labor_type_id',
        'labor_rate_id',
        'rate',
        'quantity',
        'amount',
        'workdays',
        'bonus_type_id',
        'bonus_amount',
        'target_price',
        'target_price_bonus',
        'team_id',
        'season_id',
        'user_id',
        'observations',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'integer',
        'quantity' => 'decimal:2',
        'amount' => 'integer',
        'workdays' => 'decimal:2',
        'bonus_amount' => 'integer',
        'target_price' => 'integer',
        'target_price_bonus' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function laborType()
    {
        return $this->belongsTo(LaborType::class);
    }

    public function laborRate()
    {
        return $this->belongsTo(LaborRate::class);
    }

    public function bonusType()
    {
        return $this->belongsTo(BonusType::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function costCenters()
    {
        return $this->hasMany(DailyYieldCostCenter::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
