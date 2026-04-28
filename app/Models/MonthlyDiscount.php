<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'contract_id',
        'monthly_discount_type_id',
        'month_id',
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

    public function discountType()
    {
        return $this->belongsTo(MonthlyDiscountType::class, 'monthly_discount_type_id');
    }

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
