<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyDiscountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function monthlyDiscounts()
    {
        return $this->hasMany(MonthlyDiscount::class);
    }
}
