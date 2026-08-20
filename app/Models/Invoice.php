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
        return $this->belongsToMany(Product::class, 'invoice_products')->withPivot(['id', 'unit_price', 'amount', 'observations', 'is_exento', 'branch_id', 'tank_id'])->withTimestamps();
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

    public function creditDebitNotes()
    {
        return $this->hasMany(CreditDebitNote::class);
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

    /**
     * Cálculo centralizado de deuda real de la factura (con IVA, notas de crédito/débito y rendiciones).
     * Usado tanto por el listado de InvoicePaymentController como por el informe de deuda por razón social.
     * Requiere 'typeDocument' y 'creditDebitNotes.items' cargados (eager load); 'invoiceProducts'/'payments'
     * solo si no se pasan $totalNeto/$totalPaid ya calculados (p.ej. vía subquery SQL).
     */
    public function calculateDebt(?float $totalNeto = null, ?float $totalPaid = null): array
    {
        $totalNeto = $totalNeto ?? (float) $this->invoiceProducts->sum(fn($ip) => $ip->unit_price * $ip->amount);
        $totalPaid = $totalPaid ?? (float) $this->payments->sum('amount');

        $tipoDoc      = strtoupper($this->typeDocument?->name ?? '');
        $hasIva       = in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO']);
        $iva          = $hasIva ? round($totalNeto * 0.19) : 0;
        $totalInvoice = round($totalNeto + $iva);

        // Notas de crédito/débito asociadas (llevan IVA igual que la factura)
        $notes = $this->creditDebitNotes->map(function ($note) {
            $netoNota = $note->items->sum(fn($it) => $it->quantity * $it->unit_price);
            return [
                'id'                => $note->id,
                'type'              => $note->type,
                'number'            => $note->number,
                'is_annulment'      => (bool) $note->is_annulment,
                'affects_inventory' => (bool) $note->affects_inventory,
                'total'             => round($netoNota * 1.19),
            ];
        });

        $isAnnulled  = $this->creditDebitNotes->contains(fn($n) => (bool) $n->is_annulment);
        $creditTotal = $notes->where('type', 'credito')->sum('total');
        $debitTotal  = $notes->where('type', 'debito')->sum('total');

        // NC financiera (affects_inventory=false) ya está descontada en el precio → no se resta otra vez.
        // NC de inventario sí reduce lo que se paga. Nota de débito aumenta lo que se paga.
        $inventoryCreditTotal = $notes
            ->where('type', 'credito')
            ->where('affects_inventory', true)
            ->where('is_annulment', false)
            ->sum('total');

        // Una factura vinculada a una rendición ya fue cubierta por ese proceso
        $paidViaExpenseReport = !$isAnnulled && $this->expense_report_id !== null;

        if ($isAnnulled) {
            // Una anulación cancela el 100% de la factura: no debe pagarse
            $status  = 'annulled';
            $owed    = 0;
            $balance = 0;
        } else {
            $owed = max(0, round($totalInvoice - $inventoryCreditTotal + $debitTotal));

            if ($paidViaExpenseReport) {
                $status    = 'paid';
                $totalPaid = $owed;
                $balance   = 0;
            } else {
                $balance = max(0, round($owed - $totalPaid));

                if ($owed <= 0 || $totalPaid >= $owed) {
                    $status = 'paid';
                } elseif ($totalPaid > 0) {
                    $status = 'partial';
                } else {
                    $status = 'pending';
                }
            }
        }

        return [
            'total_neto'              => $totalNeto,
            'iva'                     => $iva,
            'total_invoice'           => $totalInvoice,
            'total_paid'              => $totalPaid,
            'owed'                    => $owed,
            'balance'                 => $balance,
            'status'                  => $status,
            'is_annulled'             => $isAnnulled,
            'paid_via_expense_report' => $paidViaExpenseReport,
            'credit_total'            => $creditTotal,
            'debit_total'             => $debitTotal,
            'notes'                   => $notes->values(),
        ];
    }
}
