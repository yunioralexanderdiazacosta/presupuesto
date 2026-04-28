<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'contract_id',
        'monthly_bonus_type_id',
        'month_id',
        'labor_type_id',
        'amount',
        'observations',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function bonusType()
    {
        return $this->belongsTo(MonthlyBonusType::class, 'monthly_bonus_type_id');
    }

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'monthly_bonus_cost_centers');
    }

    public function laborType()
    {
        return $this->belongsTo(LaborType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
