<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportItem;
use Illuminate\Support\Facades\Auth;

class DeleteExpenseReportItemController extends Controller
{
    public function __invoke(ExpenseReportItem $item)
    {
        $user = Auth::user();
        $report = $item->expenseReport;

        if ($report->team_id !== $user->team_id) {
            abort(403);
        }

        if ($report->status !== 'borrador') {
            return back()->withErrors(['status' => 'Solo se pueden eliminar items de rendiciones en borrador.']);
        }

        // Eliminar archivo físico si existe
        if ($item->receipt_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($item->receipt_path);
        }

        $item->delete();

        return redirect()->route('expense-reports.show', $report->id)
            ->with('success', 'Documento eliminado de la rendición.');
    }
}
