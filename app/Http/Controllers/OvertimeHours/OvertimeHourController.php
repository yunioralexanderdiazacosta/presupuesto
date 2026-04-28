<?php

namespace App\Http\Controllers\OvertimeHours;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\CostCenter;
use App\Models\Grouping;
use App\Models\LaborType;
use App\Models\Month;
use App\Models\OvertimeHour;
use App\Models\OvertimeType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OvertimeHourController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $seasonId = session('season_id');

        $overtimeHours = OvertimeHour::with([
            'contract.employee',
            'month',
            'laborType',
            'overtimeType',
            'costCenters',
            'user',
        ])
            ->where('team_id', $user->team_id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($oh) => [
                'id'                 => $oh->id,
                'employee_name'      => $oh->contract->employee->full_name ?? '-',
                'contract_id'        => $oh->contract_id,
                'month_id'           => $oh->month_id,
                'month_name'         => $oh->month->name ?? '-',
                'labor_type_id'      => $oh->labor_type_id,
                'labor_type_name'    => $oh->laborType->name ?? '-',
                'overtime_type_id'   => $oh->overtime_type_id,
                'overtime_type_name'          => $oh->overtimeType->name ?? '-',
                // Snapshots: valores fijados al momento del registro (auditoría)
                'base_salary_snapshot'        => $oh->base_salary_snapshot,
                'hourly_rate_factor_snapshot' => $oh->hourly_rate_factor_snapshot,
                'overtime_multiplier_snapshot'=> $oh->overtime_multiplier_snapshot,
                'hours'                       => $oh->hours,
                'cost_center_ids'             => $oh->costCenters->pluck('id')->toArray(),
                'cost_center_names'           => $oh->costCenters->pluck('name')->implode(', '),
                'observations'                => $oh->observations,
                'created_by'                  => $oh->user->name ?? '-',
                'created_at'                  => $oh->created_at?->format('d/m/Y H:i'),
            ]);

        // Catálogos
        $contracts = Contract::with('employee')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'value'       => $c->id,
                'label'       => $c->employee->full_name ?? "Contrato #{$c->id}",
                'base_salary' => $c->base_salary,
            ]);

        $months = Month::orderBy('id')->get(['id', 'name'])
            ->map(fn($m) => ['value' => $m->id, 'label' => $m->name]);

        $overtimeTypes = OvertimeType::where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'value'              => $t->id,
                'label'              => $t->name,
                'hourly_rate_factor' => $t->hourly_rate_factor,
                'overtime_multiplier'=> $t->overtime_multiplier,
            ]);

        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        $groupings = Grouping::with(['costCenters' => function ($q) use ($seasonId) {
                $q->select('cost_centers.id', 'cost_centers.name')
                  ->where('season_id', $seasonId);
            }])
            ->where('season_id', $seasonId)
            ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
            ->orderBy('name')
            ->get()
            ->map(fn($g) => [
                'id'          => $g->id,
                'name'        => $g->name,
                'cost_centers' => $g->costCenters->map(fn($cc) => [
                    'id'   => $cc->id,
                    'name' => $cc->name,
                ])->values(),
            ]);

        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($l) => [
                'value'     => $l->id,
                'label'     => $l->code . ' - ' . $l->name,
                'level3_id' => $l->level3_id,
            ]);

        $level3s = \App\Models\Level3::from('level3s as l3')
            ->join('level2s as l2', 'l2.id', 'l3.level2_id')
            ->join('level1s as l1', 'l1.id', 'l2.level1_id')
            ->select('l3.id', 'l3.name')
            ->where('l1.team_id', $user->team_id)
            ->where('l1.season_id', $seasonId)
            ->where('l2.name', 'mano de obra')
            ->orderBy('l3.name')
            ->get()
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);

        return Inertia::render('OvertimeHours/Index', [
            'overtimeHours' => $overtimeHours,
            'contracts'     => $contracts,
            'months'        => $months,
            'overtimeTypes' => $overtimeTypes,
            'costCenters'   => $costCenters,
            'groupings'     => $groupings,
            'laborTypes'    => $laborTypes,
            'level3s'       => $level3s,
        ]);
    }
}
