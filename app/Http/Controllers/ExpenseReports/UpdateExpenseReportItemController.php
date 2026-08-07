<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UpdateExpenseReportItemController extends Controller
{
    public function __invoke(Request $request, ExpenseReportItem $item)
    {
        $user = Auth::user();
        $report = $item->expenseReport;

        if ($report->team_id !== $user->team_id) {
            abort(403);
        }

        // Solo se pueden editar items mientras la rendición esté en borrador
        if ($report->status !== 'borrador') {
            return back()->withErrors(['status' => 'Solo se pueden editar items de rendiciones en borrador.']);
        }

        $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'type_document_id' => 'required|exists:type_documents,id',
            'document_number' => 'required|string|max:50',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:1',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'notes' => 'nullable|string|max:500',
        ]);

        $data = [
            'date' => $request->date,
            'supplier_id' => $request->supplier_id,
            'type_document_id' => $request->type_document_id,
            'document_number' => $request->document_number,
            'product_name' => $request->product_name,
            'description' => $request->description,
            'amount' => $request->amount,
            'notes' => $request->notes,
        ];

        // Reemplazar comprobante solo si se subió uno nuevo
        if ($request->hasFile('receipt')) {
            if ($item->receipt_path) {
                Storage::disk('public')->delete($item->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store(
                "expense-receipts/{$user->team_id}/" . date('Y'),
                'public'
            );
        }

        $item->update($data);

        return redirect()->route('expense-reports.show', $report->id)
            ->with('success', 'Documento actualizado.');
    }
}
