<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'employee_id',
        'company_reason_id',
        'schedule_id',
        'contract_date',
        'contract_type',
        'position',
        'labor',
        'base_salary',
        'net_salary',
        'afp_id',
        'health_plan_id',
        'city_id',
        'parcel_id',
        'branch_id',
        'marital_status',
        'phone',
        'address',
        'email',
        'end_date',
        'is_active',
        'payment_method_id',
        'bank_id',
        'account_type_id',
        'account_number',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'base_salary' => 'integer',
        'net_salary' => 'integer',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function afp()
    {
        return $this->belongsTo(Afp::class);
    }

    public function healthPlan()
    {
        return $this->belongsTo(HealthPlan::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }

    public function vacationEntitlement()
    {
        return $this->hasOne(VacationEntitlement::class, 'employee_id', 'employee_id');
    }

    public function vacations()
    {
        return $this->hasMany(Vacation::class);
    }
}
