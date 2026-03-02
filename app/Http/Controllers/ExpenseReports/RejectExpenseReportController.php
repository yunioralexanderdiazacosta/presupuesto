<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Mail\ExpenseReportRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class RejectExpenseReportController extends Controller
{
    public function __invoke(Request $request, ExpenseReport $expenseReport)
    {
        if ($expenseReport->status !== 'enviada') {
            $statusMessages = [
                'borrador' => 'Esta rendición aún está en borrador.',
                'aprobada' => 'Esta rendición ya fue aprobada.',
                'pagada' => 'Esta rendición ya fue pagada.',
                'rechazada' => 'Esta rendición ya fue rechazada anteriormente.',
            ];

            $message = $statusMessages[$expenseReport->status] ?? 'Esta rendición ya no está pendiente de aprobación.';
            return view('expense-reports.already-processed', [
                'expenseReport' => $expenseReport,
                'message' => $message,
                'action' => 'rechazar',
            ]);
        }

        // POST: procesar el rechazo
        if ($request->isMethod('post')) {
            $request->validate([
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();
            try {
                $rejectedByName = $expenseReport->assignedTo?->name ?? 'Sistema';

                $expenseReport->update([
                    'status' => 'rechazada',
                    'rejection_notes' => $request->rejection_reason,
                ]);

                // Notificar al rendidor
                if ($expenseReport->user && $expenseReport->user->email) {
                    Mail::to($expenseReport->user->email)
                        ->send(new ExpenseReportRejected($expenseReport, $request->rejection_reason, $rejectedByName));
                }

                DB::commit();

                return view('expense-reports.rejected-confirmation', [
                    'expenseReport' => $expenseReport,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error al rechazar la rendición: ' . $e->getMessage());
            }
        }

        // GET: mostrar formulario de rechazo
        return view('expense-reports.reject-form', [
            'expenseReport' => $expenseReport,
        ]);
    }
}
