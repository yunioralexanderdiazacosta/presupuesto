<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'first_name',
        'second_name',
        'paternal_surname',
        'maternal_surname',
        'rut',
        'birth_date',
        'nationality',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_name'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('is_active', true)->latestOfMany('contract_date');
    }

    public function latestContract()
    {
        return $this->hasOne(Contract::class)->latestOfMany('contract_date');
    }

    public function getFullNameAttribute()
    {
        $parts = array_filter([
            $this->first_name,
            $this->second_name,
            $this->paternal_surname,
            $this->maternal_surname,
        ]);
        return implode(' ', $parts);
    }
}
