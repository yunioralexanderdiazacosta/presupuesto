<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDebitNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_debit_note_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
    ];

    public function creditDebitNote()
    {
        return $this->belongsTo(CreditDebitNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
