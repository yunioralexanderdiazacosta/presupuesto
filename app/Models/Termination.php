<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Termination extends Model
{
    protected $fillable = [
        'team_id',
        'contract_id',
        'employee_id',
        'causal_termino_id',
        'fecha_termino',
        'notas',
        'settlement',
        'vacation_days',
        'indemnification',
        'notice_month',
        'years_of_service',
        'afc_discount',
        'created_by',
    ];

    protected $casts = [
        'fecha_termino' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function causalTermino(): BelongsTo
    {
        return $this->belongsTo(CausalTermino::class, 'causal_termino_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
