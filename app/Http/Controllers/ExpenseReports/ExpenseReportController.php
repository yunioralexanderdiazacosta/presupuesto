<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ExpenseReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        $reports = ExpenseReport::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->with(['user:id,name', 'approvedBy:id,name', 'assignedTo:id,name'])
            ->withCount('items')
            ->withSum('items', 'amount')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'number' => $report->number,
                    'description' => $report->description,
                    'status' => $report->status,
                    'status_label' => $report->status_label,
                    'status_color' => $report->status_color,
                    'user_name' => $report->user->name ?? '',
                    'approved_by_name' => $report->approvedBy->name ?? null,
                    'assigned_to' => $report->assigned_to,
                    'assigned_to_name' => $report->assignedTo->name ?? null,
                    'approved_at' => $report->approved_at?->format('d/m/Y'),
                    'items_count' => $report->items_count,
                    'total_amount' => (float) ($report->items_sum_amount ?? 0),
                    'rejection_notes' => $report->rejection_notes,
                    'created_at' => $report->created_at->format('d/m/Y'),
                ];
            });

        // Proveedores y productos para los formularios
        $suppliers = Supplier::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'rut'])
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name . ($s->rut ? " ({$s->rut})" : '')]);

        $products = Product::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);

        // Aprobadores: usuarios con rol 'Aprobador Rendiciones' del mismo equipo
        $approvers = collect([]);
        if (Role::where('name', 'Aprobador Rendiciones')->exists()) {
            $approvers = User::role('Aprobador Rendiciones')
                ->where('team_id', $user->team_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($a) => ['value' => $a->id, 'label' => $a->name]);
        }

        return Inertia::render('ExpenseReports/Index', [
            'reports' => $reports,
            'suppliers' => $suppliers,
            'products' => $products,
            'approvers' => $approvers,
            'authUserId' => $user->id,
        ]);
    }
}
