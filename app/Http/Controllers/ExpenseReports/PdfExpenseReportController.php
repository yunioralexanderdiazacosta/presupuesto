<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExpenseReportController extends Controller
{
    public function __invoke(ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        $expenseReport->load([
            'user:id,name',
            'approvedBy:id,name',
            'assignedTo:id,name',
            'team:id,name',
            'items' => function ($query) {
                $query->orderBy('date');
            },
            'items.supplier:id,name,rut',
            'items.invoice:id,number_document,date',
            'items.typeDocument:id,name',
        ]);

        $pdf = Pdf::loadView('pdfs.expense-report', [
            'report' => $expenseReport,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'rendicion-' . $expenseReport->number . '.pdf';

        return $pdf->stream($filename);
    }
}
