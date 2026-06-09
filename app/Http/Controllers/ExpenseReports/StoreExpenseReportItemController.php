<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Traits\CheckSeasonLocked;

class StoreExpenseReportItemController extends Controller
{
    public function __invoke(Request $request, ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        // Solo se pueden agregar items en borrador
        if ($expenseReport->status !== 'borrador') {
            return back()->withErrors(['status' => 'Solo se pueden agregar items a rendiciones en borrador.']);
        }

        $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'type_document_id' => 'nullable|exists:type_documents,id',
            'document_number' => 'nullable|string|max:50',
            'product_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:1',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'notes' => 'nullable|string|max:500',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store(
                "expense-receipts/{$user->team_id}/" . date('Y'),
                'public'
            );
        }

        ExpenseReportItem::create([
            'expense_report_id' => $expenseReport->id,
            'date' => $request->date,
            'supplier_id' => $request->supplier_id,
            'type_document_id' => $request->type_document_id,
            'document_number' => $request->document_number,
            'product_name' => $request->product_name,
            'description' => $request->description,
            'amount' => $request->amount,
            'receipt_path' => $receiptPath,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expense-reports.show', $expenseReport->id)
            ->with('success', 'Documento agregado a la rendición.');
    }
}
