<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateExpenseReportController extends Controller
{
    public function __invoke(Request $request, ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        // La descripción puede editarse en cualquier estado de la rendición.
        $request->validate([
            'description' => 'nullable|string|max:500',
        ]);

        $expenseReport->update([
            'description' => $request->description,
        ]);

        return redirect()->route('expense-reports.show', $expenseReport->id)
            ->with('success', 'Rendición actualizada.');
    }
}
