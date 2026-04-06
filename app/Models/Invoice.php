<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['number','payment_term','payment_type','team_id','user_id','supplier_id','company_reason_id','type_document_id','number_document','date','due_date', 'season_id', 'month_id', 'purchase_order_id', 'expense_report_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function month()
    {
        return $this->belongsTo(\App\Models\Month::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_products')->withPivot(['id', 'unit_price', 'amount', 'observations'])->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function invoiceProducts()
    {
        return $this->hasMany(\App\Models\InvoiceProduct::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function expenseReport()
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalInvoiceAttribute()
    {
        return $this->invoiceProducts()->sum(\DB::raw('unit_price * amount'));
    }

    public function getBalanceAttribute()
    {
        return $this->total_invoice - $this->total_paid;
    }

    public function getPaymentStatusAttribute()
    {
        $totalPaid = $this->total_paid;
        $totalInvoice = $this->total_invoice;

        if ($totalPaid == 0) {
            return 'pending'; // Pendiente
        } elseif ($totalPaid >= $totalInvoice) {
            return 'paid'; // Pagado
        } else {
            return 'partial'; // Parcial
        }
    }
}
