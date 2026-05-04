<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    use HasFactory;

    protected $fillable = ['type_machinery_id','company_reason_id','branch_id','cod_machinery','brand','modelo','patente','year','volume','observations','is_active','team_id','counter_id'];

    public function typeMachinery()
    {
        return $this->belongsTo(TypeMachinery::class);
    }

    public function companyReason()
    {
        return $this->belongsTo(\App\Models\CompanyReason::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    // Relación: Una maquinaria pertenece a un counter
    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
