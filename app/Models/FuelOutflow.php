<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelOutflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'machinery_id',
        'operator_id',
        'cost_center_id',
        'product_id',
        'tank_id',
        'invoice_product_id',
        'credit_debit_note_item_id',
        'counter_id',
        'counter_value',
        'liters',
        'date',
        'observations',
    ];
    // ...existing code...

    // Relaciones
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tank()
    {
        return $this->belongsTo(FuelTank::class, 'tank_id');
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    // Relación igual que en Outflow: obtener los pivotes
    public function costCenters()
    {
        return $this->hasMany(FuelOutflowCostCenter::class);
    }

    // Nuevas relaciones para rastrear origen del combustible
    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }

    public function creditDebitNoteItem()
    {
        return $this->belongsTo(CreditDebitNoteItem::class);
    }

    // Relación con el registro de outflow correspondiente
    public function outflow()
    {
        return $this->hasOne(Outflow::class);
    }
}
