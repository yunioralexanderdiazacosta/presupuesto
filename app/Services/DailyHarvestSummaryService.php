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
     *
     * @param  array<int>  $branchIds  Vacío = todas las sucursales
     * @param  array<int>  $reasonIds  Vacío = todas las razones sociales
     */
    public function build(int $teamId, int $seasonId, string $date, array $branchIds = [], array $reasonIds = []): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $from = $day->copy()->subDays(self::TREND_DAYS - 1);

        $dispatches = ProductionDispatch::with([
                'costCenterVariety.costCenter:id,name',
                'costCenterVariety.variety:id,name',
                'exporter:id,name',
                'packingHouse:id,name',
            ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->whereBetween('dispatch_date', [$from->toDateString(), $day->toDateString()])
            ->when($branchIds, fn($q) => $q->whereHas('costCenterVariety.costCenter', fn($c) => $c->whereIn('branch_id', $branchIds)))
            ->when($reasonIds, fn($q) => $q->whereHas('costCenterVariety.costCenter', fn($c) => $c->whereIn('company_reason_id', $reasonIds)))
            ->get();

        $today = $dispatches->filter(fn($d) => $d->dispatch_date->toDateString() === $day->toDateString());

        return [
            'date' => $day->toDateString(),
            'kpis' => $this->kpis($today),
            'by_variety' => $this->groupKg($today, fn($d) => [
                (string) ($d->costCenterVariety->variety_id ?? 0),
                $d->costCenterVariety->variety->name ?? 'Sin variedad',
            ]),
            'by_cost_center' => $this->byCostCenter($today),
            'by_exporter' => $this->groupKg($today, fn($d) => [
                (string) $d->exporter_id,
                $d->exporter->name ?? 'Sin exportadora',
            ]),
            'by_packing_house' => $this->groupKg($today, fn($d) => [
                (string) $d->packing_house_id,
                $d->packingHouse->name ?? 'Sin packing',
            ]),
            'trend' => $this->trend($dispatches, $from),
        ];
    }

    private function kpis(Collection $dispatches): array
    {
        $plates = $dispatches
            ->map(fn($d) => strtoupper(trim((string) $d->license_plate)))
            ->filter();

        // Una guía sin patente se cuenta como un camión propio
        $withoutPlate = $dispatches->count() - $dispatches->filter(fn($d) => trim((string) $d->license_plate) !== '')->count();

        return [
            'kg' => round((float) $dispatches->sum('kg_dispatched'), 2),
            'guides' => $dispatches->count(),
            'trucks' => $plates->unique()->count() + $withoutPlate,
            'bins' => (int) $dispatches->sum('bins_quantity'),
            'boxes' => (int) $dispatches->sum('boxes_quantity'),
            'cost_centers' => $dispatches->pluck('costCenterVariety.cost_center_id')->unique()->count(),
        ];
    }

    /** @param callable(ProductionDispatch): array{0: string, 1: string} $keyAndName */
    private function groupKg(Collection $dispatches, callable $keyAndName): array
    {
        return $dispatches
            ->groupBy(fn($d) => $keyAndName($d)[0])
            ->map(fn($group) => [
                'name' => $keyAndName($group->first())[1],
                'kg' => round((float) $group->sum('kg_dispatched'), 2),
                'guides' => $group->count(),
            ])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    private function byCostCenter(Collection $dispatches): array
    {
        return $dispatches
            ->groupBy(fn($d) => $d->costCenterVariety->cost_center_id ?? 0)
            ->map(fn($group) => [
                'name' => $group->first()->costCenterVariety->costCenter->name ?? 'Sin cuartel',
                'varieties' => $group->map(fn($d) => $d->costCenterVariety->variety->name ?? null)->filter()->unique()->implode(', '),
                'kg' => round((float) $group->sum('kg_dispatched'), 2),
                'guides' => $group->count(),
            ])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    private function trend(Collection $dispatches, Carbon $from): array
    {
        $byDate = $dispatches->groupBy(fn($d) => $d->dispatch_date->toDateString());

        return collect(range(0, self::TREND_DAYS - 1))->map(function ($i) use ($from, $byDate) {
            $d = $from->copy()->addDays($i);
            $group = $byDate->get($d->toDateString(), collect());

            return [
                'date' => $d->toDateString(),
                'label' => $d->format('d-m'),
                'kg' => round((float) $group->sum('kg_dispatched'), 2),
                'guides' => $group->count(),
            ];
        })->all();
    }
}
