<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'bank_id',
        'account_type_id',
        'account_number',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }
}
