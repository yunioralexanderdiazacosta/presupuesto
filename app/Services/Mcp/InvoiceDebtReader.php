<?php

namespace App\Services\Mcp;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Lectura de facturas con su deuda calculada. La deuda sale siempre de Invoice::calculateDebt()
 * (misma fuente que la pantalla "Pago de Facturas"), de modo que la IA y el sistema muestran las mismas cifras.
 */
class InvoiceDebtReader
{
    public const PAYMENT_TYPES = ['credit' => '1', 'cash' => '2'];

    public function __construct(private readonly int $teamId, private readonly int $seasonId)
    {
    }

    /**
     * @param  array{payment_type?:string,supplier?:string,search?:string,date_from?:string,date_to?:string,due_from?:string,due_to?:string,company_reason?:string,invoice_id?:int}  $filters
     * @return Collection<int, array{invoice:Invoice, debt:array, overdue:bool, days_overdue:int}>
     */
    public function rows(array $filters = []): Collection
    {
        $paymentType = self::PAYMENT_TYPES[$filters['payment_type'] ?? 'credit'] ?? null;
        $today = now()->startOfDay();

        return Invoice::query()
            ->with([
                'invoiceProducts',
                'supplier:id,name,rut',
                'typeDocument',
                'companyReason:id,name',
                'payments',
                'creditDebitNotes.items',
                'expenseReport:id,number',
            ])
            ->where('invoices.team_id', $this->teamId)
            ->where('invoices.season_id', $this->seasonId)
            ->when($paymentType, fn ($q, $type) => $q->where('invoices.payment_type', $type))
            ->when($filters['invoice_id'] ?? null, fn ($q, $id) => $q->where('invoices.id', $id))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $like = $this->like($term);
                $q->where(fn ($q2) => $q2
                    ->where('invoices.number_document', 'like', $like)
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', $like)));
            })
            ->when($filters['supplier'] ?? null, fn ($q, $name) => $q->whereHas('supplier', fn ($sq) => $sq
                ->where(fn ($s) => $s->where('name', 'like', $this->like($name))->orWhere('rut', 'like', $this->like($name)))))
            ->when($filters['company_reason'] ?? null, fn ($q, $name) => $q->whereHas('companyReason', fn ($cq) => $cq
                ->where('name', 'like', $this->like($name))))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('invoices.date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('invoices.date', '<=', $date))
            ->when($filters['due_from'] ?? null, fn ($q, $date) => $q->whereDate('invoices.due_date', '>=', $date))
            ->when($filters['due_to'] ?? null, fn ($q, $date) => $q->whereDate('invoices.due_date', '<=', $date))
            ->get()
            ->map(function (Invoice $invoice) use ($today) {
                $debt = $invoice->calculateDebt();
                $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : null;
                $overdue = $dueDate !== null
                    && ! $debt['is_annulled']
                    && $debt['status'] !== 'paid'
                    && $dueDate->lt($today);

                return [
                    'invoice' => $invoice,
                    'debt' => $debt,
                    'overdue' => $overdue,
                    'days_overdue' => $overdue ? (int) $dueDate->diffInDays($today) : 0,
                ];
            });
    }

    /** Estado de pago visible para la IA (anulada se informa como estado propio). */
    public static function status(array $row): string
    {
        return $row['debt']['is_annulled'] ? 'annulled' : $row['debt']['status'];
    }

    public static function matchesStatus(array $row, string $status): bool
    {
        return match ($status) {
            'overdue' => $row['overdue'],
            'unpaid' => in_array(self::status($row), ['pending', 'partial'], true),
            default => self::status($row) === $status,
        };
    }

    public static function summaryRow(array $row): array
    {
        /** @var Invoice $invoice */
        $invoice = $row['invoice'];
        $debt = $row['debt'];

        return [
            'id' => $invoice->id,
            'document_number' => $invoice->number_document,
            'document_type' => $invoice->typeDocument?->name,
            'payment_type' => (string) $invoice->payment_type === '2' ? 'cash' : 'credit',
            'date' => $invoice->date,
            'due_date' => $invoice->due_date,
            'supplier' => $invoice->supplier
                ? ['id' => $invoice->supplier->id, 'name' => $invoice->supplier->name, 'rut' => $invoice->supplier->rut]
                : null,
            'company_reason' => $invoice->companyReason?->name,
            'total' => $debt['total_invoice'],
            'amount_owed' => $debt['owed'],
            'total_paid' => $debt['total_paid'],
            'balance' => $debt['balance'],
            'status' => self::status($row),
            'is_overdue' => $row['overdue'],
            'days_overdue' => $row['days_overdue'],
        ];
    }

    /** Escapa los comodines de LIKE para que el texto del usuario se busque literalmente. */
    private function like(string $term): string
    {
        return '%' . addcslashes(trim($term), '%_\\') . '%';
    }
}
