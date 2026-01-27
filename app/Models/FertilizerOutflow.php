<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FertilizerOutflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'fertilizer_order_id',
        'date',
        'product_id',
        'invoice_product_id',
        'credit_debit_note_item_id',
        'quantity',
        'unit_id',
        'cost_center_id',
        'observations',
        'team_id',
        'season_id',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
    ];

    // Relaciones
    public function fertilizerOrder()
    {
        return $this->belongsTo(FertilizerOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }

    public function creditDebitNoteItem()
    {
        return $this->belongsTo(CreditDebitNoteItem::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function outflows()
    {
        return $this->hasMany(Outflow::class);
    }
}
