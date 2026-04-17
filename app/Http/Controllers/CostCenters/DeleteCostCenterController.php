<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DeleteCostCenterController extends Controller
{
    public function __invoke(CostCenter $costCenter)
    {
        $usageChecks = [
            'Items de agroquímicos' => $this->countByColumn('agrochemical_items', 'cost_center_id', $costCenter->id),
            'Items de fertilizantes' => $this->countByColumn('fertilizer_items', 'cost_center_id', $costCenter->id),
            'Items de mano de obra' => $this->countByColumn('manpower_items', 'cost_center_id', $costCenter->id),
            'Items de insumos' => $this->countByColumn('supply_items', 'cost_center_id', $costCenter->id),
            'Items de servicios' => $this->countByColumn('service_items', 'cost_center_id', $costCenter->id),
            'Items de cosecha' => $this->countByColumn('harvest_items', 'cost_center_id', $costCenter->id),
            'Estimaciones' => $this->countEstimatesUsage($costCenter->id),
            'Inversiones' => $this->countByColumn('investments', 'cost_center_id', $costCenter->id),
            'Outflows generales' => $this->countByColumn('outflow_cost_center', 'cost_center_id', $costCenter->id),
            'Outflows de combustible' => $this->countByColumn('fuel_outflow_cost_center', 'cost_center_id', $costCenter->id),
            'Órdenes de fertilización' => $this->countByColumn('fertilizer_order_cost_center', 'cost_center_id', $costCenter->id),
            'Órdenes de aplicación' => $this->countByColumn('application_order_cost_center', 'cost_center_id', $costCenter->id),
            'Variedades del cuartel' => $this->countByColumn('cost_center_varieties', 'cost_center_id', $costCenter->id),
            'Agrupaciones' => $this->countByColumn('cost_center_grouping', 'cost_center_id', $costCenter->id),
            'Consumos' => $this->countByColumn('consumption_cost_center', 'cost_center_id', $costCenter->id),
            'Outflows de agroquímicos' => $this->countByColumn('agrochemical_outflows', 'cost_center_id', $costCenter->id),
            'Outflows de fertilizantes' => $this->countByColumn('fertilizer_outflows', 'cost_center_id', $costCenter->id),
            'Rendimientos diarios' => $this->countByColumn('daily_yields', 'cost_center_id', $costCenter->id),
            'Órdenes de compra' => $this->countByColumn('purchase_order_cost_center', 'cost_center_id', $costCenter->id),
        ];

        $totalUsages = array_sum($usageChecks);

        if ($totalUsages > 0) {
            $messages = [];
            $hasOutflows = false;
            foreach ($usageChecks as $label => $count) {
                if ($count > 0) {
                    $messages[] = "{$label}: {$count}";

                    if (str_contains($label, 'Outflows')) {
                        $hasOutflows = true;
                    }
                }
            }

            $reason = $hasOutflows
                ? "porque tiene outflows relacionados"
                : "porque está siendo usado en otros registros";

            return back()->withErrors([
                'error' => "No se puede eliminar el centro de costo '{$costCenter->name}' {$reason}: " . implode(', ', $messages) . ". Elimine primero estos registros."
            ]);
        }

        try {
            $costCenter->delete();
        } catch (Throwable $exception) {
            return back()->withErrors([
                'error' => 'Error al eliminar el centro de costo: ' . $exception->getMessage(),
            ]);
        }
        
        return back()->with('success', 'Centro de costo eliminado correctamente');
    }

    private function countByColumn(string $table, string $column, int $costCenterId): int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->where($column, $costCenterId)->count();
    }

    private function countEstimatesUsage(int $costCenterId): int
    {
        if (!Schema::hasTable('estimates')) {
            return 0;
        }

        if (Schema::hasColumn('estimates', 'cost_center_id')) {
            return DB::table('estimates')
                ->where('cost_center_id', $costCenterId)
                ->count();
        }

        if (
            Schema::hasColumn('estimates', 'cost_center_variety_id') &&
            Schema::hasTable('cost_center_varieties') &&
            Schema::hasColumn('cost_center_varieties', 'cost_center_id')
        ) {
            return DB::table('estimates as estimates')
                ->join('cost_center_varieties as cost_center_varieties', 'estimates.cost_center_variety_id', '=', 'cost_center_varieties.id')
                ->where('cost_center_varieties.cost_center_id', $costCenterId)
                ->count();
        }

        return 0;
    }
}
