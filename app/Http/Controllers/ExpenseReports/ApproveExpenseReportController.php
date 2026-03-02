<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Mail\ExpenseReportApproved;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ApproveExpenseReportController extends Controller
{
    public function __invoke(ExpenseReport $expenseReport)
    {
        if ($expenseReport->status !== 'enviada') {
            $statusMessages = [
                'borrador' => 'Esta rendición aún está en borrador.',
                'aprobada' => 'Esta rendición ya fue aprobada anteriormente.',
                'pagada' => 'Esta rendición ya fue pagada.',
                'rechazada' => 'Esta rendición ya fue rechazada.',
            ];

            $message = $statusMessages[$expenseReport->status] ?? 'Esta rendición ya no está pendiente de aprobación.';
            return view('expense-reports.already-processed', [
                'expenseReport' => $expenseReport,
                'message' => $message,
                'action' => 'aprobar',
            ]);
        }

        DB::beginTransaction();
        try {
            $approverName = $expenseReport->assignedTo->name ?? 'Aprobador';

            $expenseReport->update([
                'status' => 'aprobada',
                'approved_by' => $expenseReport->assigned_to,
                'approved_at' => now(),
            ]);

            // Notificar al rendidor
            if ($expenseReport->user && $expenseReport->user->email) {
                Mail::to($expenseReport->user->email)
                    ->send(new ExpenseReportApproved($expenseReport, $approverName));
            }

            DB::commit();

            return view('expense-reports.approved-confirmation', [
                'expenseReport' => $expenseReport,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al aprobar la rendición: ' . $e->getMessage());
        }
    }
}
