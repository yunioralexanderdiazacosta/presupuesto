<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\User;
use App\Mail\ExpenseReportApproved;
use App\Mail\ExpenseReportPendingApproval;
use App\Mail\ExpenseReportRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class UpdateExpenseReportStatusController extends Controller
{
    public function __invoke(Request $request, ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        $rules = [
            'status' => 'required|in:enviada,aprobada,pagada,rechazada,borrador',
            'rejection_notes' => 'nullable|string|max:500',
        ];

        // Al enviar, el assigned_to es obligatorio
        if ($request->status === 'enviada') {
            $rules['assigned_to'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        $newStatus = $request->status;

        // Validar transiciones permitidas
        $allowed = [
            'borrador' => ['enviada'],
            'enviada' => ['aprobada', 'rechazada'],
            'aprobada' => ['pagada'],
            'rechazada' => ['borrador'],
        ];

        if (!isset($allowed[$expenseReport->status]) || !in_array($newStatus, $allowed[$expenseReport->status])) {
            return back()->withErrors(['status' => 'Transición de estado no permitida.']);
        }

        // Validar que tenga items para enviar
        if ($newStatus === 'enviada' && $expenseReport->items()->count() === 0) {
            return back()->withErrors(['status' => 'La rendición debe tener al menos un documento para enviar.']);
        }

        $expenseReport->status = $newStatus;

        // Al enviar, guardar el aprobador asignado
        if ($newStatus === 'enviada') {
            $expenseReport->assigned_to = $request->assigned_to;
        }

        if (in_array($newStatus, ['aprobada', 'pagada'])) {
            $expenseReport->approved_by = $user->id;
            $expenseReport->approved_at = now();
        }

        if ($newStatus === 'rechazada') {
            $expenseReport->rejection_notes = $request->rejection_notes;
        }

        // Si se vuelve a borrador (después de rechazo), limpiar
        if ($newStatus === 'borrador') {
            $expenseReport->rejection_notes = null;
            $expenseReport->approved_by = null;
            $expenseReport->approved_at = null;
            $expenseReport->assigned_to = null;
        }

        $expenseReport->save();

        // Enviar email al aprobador asignado
        if ($newStatus === 'enviada' && $expenseReport->assigned_to) {
            $expenseReport->load(['user', 'items.supplier', 'assignedTo']);
            $approver = $expenseReport->assignedTo;
            if ($approver && $approver->email) {
                Mail::to($approver->email)
                    ->send(new ExpenseReportPendingApproval($expenseReport, $approver->name));
            }
        }

        // Enviar email de confirmación al creador cuando se aprueba desde la UI
        if ($newStatus === 'aprobada') {
            $expenseReport->load('user');
            if ($expenseReport->user && $expenseReport->user->email) {
                Mail::to($expenseReport->user->email)
                    ->send(new ExpenseReportApproved($expenseReport, $user->name));
            }
        }

        // Enviar email de rechazo al creador cuando se rechaza desde la UI
        if ($newStatus === 'rechazada') {
            $expenseReport->load('user');
            if ($expenseReport->user && $expenseReport->user->email) {
                Mail::to($expenseReport->user->email)
                    ->send(new ExpenseReportRejected($expenseReport, $request->rejection_notes, $user->name));
            }
        }

        $labels = [
            'enviada' => 'enviada para revisión',
            'aprobada' => 'aprobada',
            'pagada' => 'marcada como pagada',
            'rechazada' => 'rechazada',
            'borrador' => 'devuelta a borrador',
        ];

        return redirect()->back()
            ->with('success', "Rendición {$expenseReport->number} {$labels[$newStatus]}.");
    }
}
