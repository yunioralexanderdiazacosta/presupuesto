<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Services\BudgetCopyService;
use App\Support\ActiveBudgetVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetVersionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(ActiveBudgetVersion::isAdmin($request->user()), 403, 'Solo los administradores pueden gestionar versiones de presupuesto.');

        if (!session('season_id')) {
            return redirect()->route('select.budget');
        }

        $versions = ActiveBudgetVersion::forCurrentSeason()
            ->with('creator:id,name')
            ->orderByDesc('is_official')
            ->orderBy('id')
            ->get();

        // Cantidad de líneas de presupuesto por versión (suma de los 8 rubros)
        $lineCounts = [];
        foreach (BudgetCopyService::TYPES as $config) {
            $rows = DB::table((new $config['model'])->getTable())
                ->whereIn('budget_version_id', $versions->pluck('id'))
                ->select('budget_version_id', DB::raw('COUNT(*) as total'))
                ->groupBy('budget_version_id')
                ->pluck('total', 'budget_version_id');

            foreach ($rows as $versionId => $total) {
                $lineCounts[$versionId] = ($lineCounts[$versionId] ?? 0) + $total;
            }
        }

        $activeId = ActiveBudgetVersion::id();

        return Inertia::render('BudgetVersions/Index', [
            'versions' => $versions->map(fn($v) => [
                'id'           => $v->id,
                'name'         => $v->name,
                'observations' => $v->observations,
                'is_official'  => $v->is_official,
                'is_locked'    => $v->is_locked,
                'is_active'    => $v->id === $activeId,
                'created_by'   => $v->creator?->name,
                'created_at'   => $v->created_at?->format('d-m-Y H:i'),
                'lines'        => (int) ($lineCounts[$v->id] ?? 0),
            ])->values(),
        ]);
    }
}
