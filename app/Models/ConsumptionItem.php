<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumption_id',
        'invoice_item_id',
        'product_id',
        'quantity',
        'observations',
    ];

    public function consumption()
    {
        return $this->belongsTo(Consumption::class);
    }

    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class, 'invoice_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
