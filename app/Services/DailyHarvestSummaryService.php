<?php

namespace App\Services;

use App\Models\ProductionDispatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailyHarvestSummaryService
{
    private const TREND_DAYS = 7;

    /**
     * Resumen de cosecha de un día a partir de las guías de despacho (fecha de despacho).
     * Una guía puede llevar varios cuarteles, por eso se trabaja por "aporte": una fila por guía y cuartel
     * con los kg que ese cuartel despachó.
     *
     * @param  array<int>  $branchIds  Vacío = todas las sucursales
     * @param  array<int>  $reasonIds  Vacío = todas las razones sociales
     */
    public function build(int $teamId, int $seasonId, string $date, array $branchIds = [], array $reasonIds = []): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $from = $day->copy()->subDays(self::TREND_DAYS - 1);

        $dispatches = ProductionDispatch::with([
                'costCenters.costCenterVariety.costCenter:id,name,branch_id,company_reason_id',
                'costCenters.costCenterVariety.variety:id,name',
                'exporter:id,name',
                'packingHouse:id,name',
            ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->whereBetween('dispatch_date', [$from->toDateString(), $day->toDateString()])
            ->get();

        $rows = $this->contributions($dispatches, $branchIds, $reasonIds);
        $today = $rows->filter(fn($r) => $r['dispatch']->dispatch_date->toDateString() === $day->toDateString());

        return [
            'date' => $day->toDateString(),
            'kpis' => $this->kpis($today),
            'by_variety' => $this->groupKg($today, fn($r) => [
                (string) ($r['ccv']->variety_id ?? 0),
                $r['ccv']->variety->name ?? 'Sin variedad',
            ]),
            'by_cost_center' => $this->byCostCenter($today),
            'by_exporter' => $this->groupKg($today, fn($r) => [
                (string) $r['dispatch']->exporter_id,
                $r['dispatch']->exporter->name ?? 'Sin exportadora',
            ]),
            'by_packing_house' => $this->groupKg($today, fn($r) => [
                (string) $r['dispatch']->packing_house_id,
                $r['dispatch']->packingHouse->name ?? 'Sin packing',
            ]),
            'trend' => $this->trend($rows, $from),
        ];
    }

    /**
     * @return Collection<int, array{dispatch: ProductionDispatch, ccv: \App\Models\CostCenterVariety, kg: float}>
     */
    private function contributions(Collection $dispatches, array $branchIds, array $reasonIds): Collection
    {
        return $dispatches
            ->flatMap(fn($d) => $d->costCenters->map(fn($line) => [
                'dispatch' => $d,
                'ccv' => $line->costCenterVariety,
                'kg' => (float) $line->kg_dispatched,
            ]))
            ->filter(fn($r) => $r['ccv'] !== null)
            ->when($branchIds, fn($c) => $c->filter(fn($r) => in_array($r['ccv']->costCenter->branch_id ?? null, $branchIds)))
            ->when($reasonIds, fn($c) => $c->filter(fn($r) => in_array($r['ccv']->costCenter->company_reason_id ?? null, $reasonIds)))
            ->values();
    }

    private function guides(Collection $rows): Collection
    {
        return $rows->pluck('dispatch')->unique('id');
    }

    private function kpis(Collection $rows): array
    {
        $guides = $this->guides($rows);

        $plates = $guides
            ->map(fn($d) => strtoupper(trim((string) $d->license_plate)))
            ->filter();

        // Una guía sin patente se cuenta como un camión propio
        $withoutPlate = $guides->count() - $guides->filter(fn($d) => trim((string) $d->license_plate) !== '')->count();

        return [
            'kg' => round((float) $rows->sum('kg'), 2),
            'guides' => $guides->count(),
            'trucks' => $plates->unique()->count() + $withoutPlate,
            'bins' => (int) $guides->sum('bins_quantity'),
            'boxes' => (int) $guides->sum('boxes_quantity'),
            'cost_centers' => $rows->pluck('ccv.cost_center_id')->unique()->count(),
        ];
    }

    /** @param callable(array): array{0: string, 1: string} $keyAndName */
    private function groupKg(Collection $rows, callable $keyAndName): array
    {
        return $rows
            ->groupBy(fn($r) => $keyAndName($r)[0])
            ->map(fn($group) => [
                'name' => $keyAndName($group->first())[1],
                'kg' => round((float) $group->sum('kg'), 2),
                'guides' => $this->guides($group)->count(),
            ])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    private function byCostCenter(Collection $rows): array
    {
        return $rows
            ->groupBy(fn($r) => $r['ccv']->cost_center_id ?? 0)
            ->map(fn($group) => [
                'name' => $group->first()['ccv']->costCenter->name ?? 'Sin cuartel',
                'varieties' => $group->map(fn($r) => $r['ccv']->variety->name ?? null)->filter()->unique()->implode(', '),
                'kg' => round((float) $group->sum('kg'), 2),
                'guides' => $this->guides($group)->count(),
            ])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    private function trend(Collection $rows, Carbon $from): array
    {
        $byDate = $rows->groupBy(fn($r) => $r['dispatch']->dispatch_date->toDateString());

        return collect(range(0, self::TREND_DAYS - 1))->map(function ($i) use ($from, $byDate) {
            $d = $from->copy()->addDays($i);
            $group = $byDate->get($d->toDateString(), collect());

            return [
                'date' => $d->toDateString(),
                'label' => $d->format('d-m'),
                'kg' => round((float) $group->sum('kg'), 2),
                'guides' => $this->guides($group)->count(),
            ];
        })->all();
    }
}
