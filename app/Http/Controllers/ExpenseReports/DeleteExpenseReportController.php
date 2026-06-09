<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteExpenseReportController extends Controller
{
    public function __invoke(ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        // Eliminar archivos físicos de los items
        foreach ($expenseReport->items as $item) {
            if ($item->receipt_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($item->receipt_path);
            }
        }

        $number = $expenseReport->number;
        $expenseReport->delete();

        return redirect()->route('expense-reports.index')
            ->with('success', "Rendición {$number} eliminada.");
    }
}
