<?php

namespace App\Http\Controllers\MonthlyBonuses;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\CostCenter;
use App\Models\LaborType;
use App\Models\Month;
use App\Models\Grouping;
use App\Models\MonthlyBonus;
use App\Models\MonthlyBonusType;
use App\Models\MonthlyDiscount;
use App\Models\MonthlyDiscountType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MonthlyBonusController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $seasonId = session('season_id');

        // --- Tab: Bonos ---
        $bonuses = MonthlyBonus::with([
            'contract.employee',
            'bonusType',
            'month',
            'costCenters',
            'laborType',
            'user',
        ])
            ->where('team_id', $user->team_id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($b) => [
                'id'                => $b->id,
                'employee_name'     => $b->contract->employee->full_name ?? '-',
                'contract_id'       => $b->contract_id,
                'bonus_type_id'     => $b->monthly_bonus_type_id,
                'bonus_type_name'   => $b->bonusType->name ?? '-',
                'month_id'          => $b->month_id,
                'month_name'        => $b->month->name ?? '-',
                'cost_center_ids'   => $b->costCenters->pluck('id')->toArray(),
                'cost_center_names' => $b->costCenters->pluck('name')->implode(', '),
                'labor_type_id'     => $b->labor_type_id,
                'labor_type_name'   => $b->laborType->name ?? '-',
                'amount'            => $b->amount,
                'observations'      => $b->observations,
                'created_by'        => $b->user->name ?? '-',
                'created_at'        => $b->created_at?->format('d/m/Y H:i'),
            ]);

        // --- Tab: Descuentos ---
        $discounts = MonthlyDiscount::with([
            'contract.employee',
            'discountType',
            'month',
            'user',
        ])
            ->where('team_id', $user->team_id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'                 => $d->id,
                'employee_name'      => $d->contract->employee->full_name ?? '-',
                'contract_id'        => $d->contract_id,
                'discount_type_id'   => $d->monthly_discount_type_id,
                'discount_type_name' => $d->discountType->name ?? '-',
                'month_id'           => $d->month_id,
                'month_name'         => $d->month->name ?? '-',
                'amount'             => $d->amount,
                'observations'       => $d->observations,
                'created_by'         => $d->user->name ?? '-',
                'created_at'         => $d->created_at?->format('d/m/Y H:i'),
            ]);

        // --- Catálogos compartidos ---
        $contracts = Contract::with('employee')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->employee->full_name ?? "Contrato #{$c->id}",
            ]);

        $months = Month::orderBy('id')->get(['id', 'name'])
            ->map(fn($m) => ['value' => $m->id, 'label' => $m->name]);

        // Select activos para modales de bonos
        $bonusTypesSelect = MonthlyBonusType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name]);

        // Select activos para modales de descuentos
        $discountTypesSelect = MonthlyDiscountType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name]);

        // Lista completa para tab de catálogo de tipos de bono
        $bonusTypesCatalog = MonthlyBonusType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'is_active' => (bool) $t->is_active]);

        // Lista completa para tab de catálogo de tipos de descuento
        $discountTypesCatalog = MonthlyDiscountType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'is_active' => (bool) $t->is_active]);

        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // Agrupaciones para preselección rápida de CC
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

        // LaborTypes con level3_id para filtrado reactivo en frontend
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($l) => [
                'value'    => $l->id,
                'label'    => $l->code . ' - ' . $l->name,
                'level3_id' => $l->level3_id,
            ]);

        // Level3s para el filtro del select de labores (solo mano de obra del equipo/temporada)
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

        return Inertia::render('MonthlyBonuses/Index', [
            'bonuses'              => $bonuses,
            'discounts'            => $discounts,
            'contracts'            => $contracts,
            'months'               => $months,
            'bonusTypesSelect'     => $bonusTypesSelect,
            'discountTypesSelect'  => $discountTypesSelect,
            'bonusTypesCatalog'    => $bonusTypesCatalog,
            'discountTypesCatalog' => $discountTypesCatalog,
            'costCenters'          => $costCenters,
            'groupings'            => $groupings,
            'laborTypes'           => $laborTypes,
            'level3s'              => $level3s,
            'activeTab'            => request('tab', 'bonuses'),
        ]);
    }
}
