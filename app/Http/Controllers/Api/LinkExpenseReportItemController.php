<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReportItem;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LinkExpenseReportItemController extends Controller
{
    /**
     * Vincula manualmente un item de rendición con una factura ya existente,
     * sin crear una factura nueva (evita duplicados cuando ambas se generaron en paralelo).
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'expense_report_item_id' => 'required|integer|exists:expense_report_items,id',
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $user = Auth::user();

        $item = ExpenseReportItem::whereHas('expenseReport', fn ($q) => $q->where('team_id', $user->team_id))
            ->findOrFail($request->expense_report_item_id);

        if ($item->invoice_id) {
            return response()->json(['message' => 'Este documento ya está vinculado a una factura.'], 422);
        }

        $invoice = Invoice::where('team_id', $user->team_id)->findOrFail($request->invoice_id);

        $item->update(['invoice_id' => $invoice->id]);

        if (!$invoice->expense_report_id) {
            $invoice->update(['expense_report_id' => $item->expense_report_id]);
        }

        return response()->json(['success' => true]);
    }
}
