<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseReportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_report_id',
        'date',
        'supplier_id',
        'type_document_id',
        'document_number',
        'product_name',
        'description',
        'amount',
        'receipt_path',
        'ocr_data',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'ocr_data' => 'array',
    ];

    // ─── Relaciones ──────────────────────────────

    public function expenseReport()
    {
        return $this->belongsTo(\App\Models\ExpenseReport::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(\App\Models\TypeDocument::class);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    // ─── Accessors ───────────────────────────────

    /**
     * Si ya fue contabilizado (tiene invoice vinculado)
     */
    public function getIsContabilizedAttribute()
    {
        return !is_null($this->invoice_id);
    }
}
