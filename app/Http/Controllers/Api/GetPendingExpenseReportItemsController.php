<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReportItem;
use App\Models\Invoice;
use App\Traits\NormalizesDocumentNumber;
use Illuminate\Support\Facades\Auth;

class GetPendingExpenseReportItemsController extends Controller
{
    use NormalizesDocumentNumber;

    public function __invoke()
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        $items = ExpenseReportItem::whereNull('invoice_id')
            ->whereHas('expenseReport', function ($q) use ($user, $seasonId) {
                $q->where('team_id', $user->team_id)
                    ->where('season_id', $seasonId)
                    ->whereIn('status', ['aprobada', 'pagada']);
            })
            ->with([
                'expenseReport:id,number,status',
                'supplier:id,name',
            ])
            ->orderBy('date', 'desc')
            ->get();

        // Facturas candidatas del equipo (mismos proveedores) para detectar si el
        // documento de la rendición ya fue ingresado por otra vía (evita duplicados).
        $supplierIds = $items->pluck('supplier_id')->unique()->values();
        $candidateInvoices = Invoice::where('team_id', $user->team_id)
            ->whereIn('supplier_id', $supplierIds)
            ->get(['id', 'supplier_id', 'number_document', 'date']);

        $items = $items->map(function ($item) use ($candidateInvoices) {
            $normalizedDoc = static::normalizeDocumentNumber($item->document_number);

            $duplicate = $normalizedDoc !== ''
                ? $candidateInvoices->first(fn ($inv) => $inv->supplier_id === $item->supplier_id
                    && static::normalizeDocumentNumber($inv->number_document) === $normalizedDoc)
                : null;

            return [
                'id' => $item->id,
                'expense_report_number' => $item->expenseReport->number ?? '',
                'expense_report_id' => $item->expense_report_id,
                'date' => $item->date->format('d/m/Y'),
                'date_raw' => $item->date->format('Y-m-d'),
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier->name ?? '',
                'product_name' => $item->product_name ?? '',
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'has_receipt' => !empty($item->receipt_path),
                'duplicate_invoice' => $duplicate ? [
                    'id' => $duplicate->id,
                    'number_document' => $duplicate->number_document,
                    'date' => \Carbon\Carbon::parse($duplicate->date)->format('d/m/Y'),
                ] : null,
            ];
        });

        return response()->json($items);
    }
}
