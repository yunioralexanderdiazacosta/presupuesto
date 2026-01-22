<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgrochemicalOutflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_order_id',
        'maquinadas',
        'date',
        'product_id',
        'invoice_product_id',
        'credit_debit_note_item_id',
        'quantity',
        'cost_center_id',
        'observations',
        'team_id',
        'season_id',
    ];

    protected $casts = [
        'date' => 'date',
        'maquinadas' => 'decimal:2',
        'quantity' => 'decimal:2',
    ];

    /**
     * Relación con ApplicationOrder
     */
    public function applicationOrder()
    {
        return $this->belongsTo(ApplicationOrder::class);
    }

    /**
     * Relación con Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con InvoiceProduct (origen del stock)
     */
    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }

    /**
     * Relación con CreditDebitNoteItem (origen alternativo)
     */
    public function creditDebitNoteItem()
    {
        return $this->belongsTo(CreditDebitNoteItem::class);
    }

    /**
     * Relación con CostCenter
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Relación con Team
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación con Season
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Relación con el registro de outflow correspondiente
     */
    public function outflow()
    {
        return $this->hasOne(Outflow::class);
    }
}
