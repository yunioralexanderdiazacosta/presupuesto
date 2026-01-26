<?php

namespace App\Http\Controllers\CostCenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;

class DeleteCostCenterController extends Controller
{
    public function __invoke(CostCenter $costCenter)
    {
        // Verificar si el centro de costo está siendo usado
        $usageChecks = [
            'agrochemical_items' => DB::table('agrochemical_items')->where('cost_center_id', $costCenter->id)->count(),
            'fertilizer_items' => DB::table('fertilizer_items')->where('cost_center_id', $costCenter->id)->count(),
            'manpower_items' => DB::table('manpower_items')->where('cost_center_id', $costCenter->id)->count(),
            'supply_items' => DB::table('supply_items')->where('cost_center_id', $costCenter->id)->count(),
            'service_items' => DB::table('service_items')->where('cost_center_id', $costCenter->id)->count(),
            'harvest_items' => DB::table('harvest_items')->where('cost_center_id', $costCenter->id)->count(),
            'estimates' => DB::table('estimates')->where('cost_center_id', $costCenter->id)->count(),
            'investments' => DB::table('investments')->where('cost_center_id', $costCenter->id)->count(),
            'outflow_cost_center' => DB::table('outflow_cost_center')->where('cost_center_id', $costCenter->id)->count(),
            'fuel_outflow_cost_center' => DB::table('fuel_outflow_cost_center')->where('cost_center_id', $costCenter->id)->count(),
            'fertilizer_order_cost_center' => DB::table('fertilizer_order_cost_center')->where('cost_center_id', $costCenter->id)->count(),
            'application_order_cost_center' => DB::table('application_order_cost_center')->where('cost_center_id', $costCenter->id)->exists() ? 1 : 0,
        ];
        
        $totalUsages = array_sum($usageChecks);
        
        if ($totalUsages > 0) {
            $messages = [];
            foreach ($usageChecks as $table => $count) {
                if ($count > 0) {
                    $messages[] = ucfirst(str_replace('_', ' ', $table)) . ": {$count}";
                }
            }
            
            return back()->withErrors([
                'error' => "No se puede eliminar el centro de costo '{$costCenter->name}' porque está siendo usado en: " . implode(', ', $messages) . ". Elimine primero estos registros."
            ]);
        }
        
        $costCenter->delete();
        
        return back()->with('success', 'Centro de costo eliminado correctamente');
    }
}
