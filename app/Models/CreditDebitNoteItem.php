<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDebitNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_debit_note_id',
        'invoice_product_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'branch_id',
    ];
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }

    public function outflows()
    {
        return $this->hasMany(Outflow::class);
    }

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

    /** Monto de la línea sin signo (cantidad × precio unitario). */
    public function getAmountAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    /** Monto de la línea con signo, según el tipo de la nota a la que pertenece (crédito resta, débito suma). */
    public function getSignedAmountAttribute(): float
    {
        $type = $this->relationLoaded('creditDebitNote')
            ? $this->creditDebitNote?->type
            : $this->creditDebitNote()->value('type');
        return $type === CreditDebitNote::TYPE_CREDIT ? -$this->amount : $this->amount;
    }
}
