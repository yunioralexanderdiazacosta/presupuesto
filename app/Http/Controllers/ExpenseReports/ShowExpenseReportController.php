<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\ExpenseReportItem;
use App\Models\Supplier;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ShowExpenseReportController extends Controller
{
    public function __invoke(ExpenseReport $expenseReport)
    {
        $user = Auth::user();

        // Seguridad: solo puede ver rendiciones de su equipo
        if ($expenseReport->team_id !== $user->team_id) {
            abort(403);
        }

        $expenseReport->load([
            'user:id,name',
            'approvedBy:id,name',
            'assignedTo:id,name',
            'items.supplier:id,name,rut',
            'items.invoice:id,number_document,date',
            'items.typeDocument:id,name',
        ]);

        $report = [
            'id' => $expenseReport->id,
            'number' => $expenseReport->number,
            'description' => $expenseReport->description,
            'status' => $expenseReport->status,
            'status_label' => $expenseReport->status_label,
            'status_color' => $expenseReport->status_color,
            'user_name' => $expenseReport->user->name ?? '',
            'user_id' => $expenseReport->user_id,
            'assigned_to' => $expenseReport->assigned_to,
            'assigned_to_name' => $expenseReport->assignedTo->name ?? null,
            'approved_by_name' => $expenseReport->approvedBy->name ?? null,
            'approved_at' => $expenseReport->approved_at?->format('d/m/Y H:i'),
            'rejection_notes' => $expenseReport->rejection_notes,
            'created_at' => $expenseReport->created_at->format('d/m/Y'),
            'total_amount' => (float) $expenseReport->total_amount,
            'contabilized_amount' => (float) $expenseReport->contabilized_amount,
            'pending_amount' => (float) $expenseReport->pending_amount,
            'items' => $expenseReport->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date->format('Y-m-d'),
                    'date_formatted' => $item->date->format('d/m/Y'),
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->name ?? '',
                    'type_document_id' => $item->type_document_id,
                    'type_document_name' => $item->typeDocument->name ?? '',
                    'document_number' => $item->document_number,
                    'product_name' => $item->product_name ?? '',
                    'description' => $item->description,
                    'amount' => (float) $item->amount,
                    'receipt_path' => $item->receipt_path,
                    'invoice_id' => $item->invoice_id,
                    'invoice_number' => $item->invoice->number_document ?? null,
                    'is_contabilized' => $item->is_contabilized,
                    'notes' => $item->notes,
                ];
            }),
        ];

        // Proveedores y productos para formularios
        $suppliers = Supplier::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'rut'])
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name . ($s->rut ? " ({$s->rut})" : '')]);

        $typeDocuments = TypeDocument::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name]);

        // Aprobadores
        $approvers = collect([]);
        if (Role::where('name', 'Aprobador Rendiciones')->exists()) {
            $approvers = User::role('Aprobador Rendiciones')
                ->where('team_id', $user->team_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($a) => ['value' => $a->id, 'label' => $a->name]);
        }

        return Inertia::render('ExpenseReports/Show', [
            'report' => $report,
            'suppliers' => $suppliers,
            'typeDocuments' => $typeDocuments,
            'approvers' => $approvers,
            'authUserId' => $user->id,
        ]);
    }
}
