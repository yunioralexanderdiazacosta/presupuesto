<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'team_id',
        'season_id',
        'user_id',
        'bank_id',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_number',
        'observations'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function getPaymentMethodNameAttribute()
    {
        $methods = [
            1 => 'Transferencia',
            2 => 'Efectivo',
            3 => 'Cheque'
        ];
        return $methods[$this->payment_method] ?? 'Desconocido';
    }
}
