<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

trait BudgetTotalsTrait
{
    // Debes asignar $this->month_id antes de usar estos métodos

    // Calcula el total global de fields (Generales campo)
    public function getTotalField($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $fields = \App\Models\Field::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($fields->isEmpty()) return 0;
        $fieldIds = $fields->keys();
        $items = DB::table('field_items')
            ->select('field_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('field_id', $fieldIds)
            ->whereIn('month_id', $months)
            ->groupBy('field_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $field = $fields[$item->field_id];
            $quantity = ($field->quantity !== null && ($field->quantity > 0)) ? ((in_array($field->unit_id ?? null, [2,4])) ? ($field->quantity / 1000) : $field->quantity) : 0;
            $amount = round($field->price * $quantity * $item->months, 2);
            $total += $amount;
        }
        return $total;
    }

    // Calcula el total global de administración
    public function getTotalAdministration($season_id, $team_id)
    {
        $administrations = \App\Models\Administration::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($administrations->isEmpty()) return 0;
        $admIds = $administrations->keys();
        $items = DB::table('administration_items')
            ->select('administration_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('administration_id', $admIds)
            ->groupBy('administration_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $adm = $administrations[$item->administration_id];
            $quantity = (($adm->unit_id == 4) || ($adm->unit_id == 2)) ? ($adm->quantity / 1000) : $adm->quantity;
            $amount = round($adm->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de fertilizantes (opcionalmente por fruta)
    public function getTotalFertilizer($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $fertilizers = \App\Models\Fertilizer::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $fertilizers->isEmpty()) return 0;
        $fertIds = $fertilizers->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('fertilizer_id', $fertIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $fert = $fertilizers[$item->fertilizer_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $dose = (($fert->unit_id == 4 && $fert->unit_id_price == 3) || ($fert->unit_id == 2 && $fert->unit_id_price == 1)) ? ($fert->dose / 1000) : $fert->dose;
            $quantity = round($dose * $surface, 2);
            $amount = round($fert->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de mano de obra (opcionalmente por fruta)
    public function getTotalManPower($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $manpowers = \App\Models\ManPower::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $manpowers->isEmpty()) return 0;
        $mpIds = $manpowers->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('man_power_id', $mpIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $mp = $manpowers[$item->man_power_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $quantity = round($mp->workday * $surface, 2);
            $amount = round($mp->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de agroquímicos (opcionalmente por fruta)
    public function getTotalAgrochemical($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $agrochemicals = \App\Models\Agrochemical::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $agrochemicals->isEmpty()) return 0;
        $agroIds = $agrochemicals->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('agrochemical_id', $agroIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $agro = $agrochemicals[$item->agrochemical_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $dose = (($agro->unit_id == 4 && $agro->unit_id_price == 3) || ($agro->unit_id == 2 && $agro->unit_id_price == 1)) ? ($agro->dose / 1000) : $agro->dose;
            if ($agro->dose_type_id == 1) {
                $quantity = round($dose * $surface, 2);
            } elseif ($agro->dose_type_id == 2) {
                $quantity = round((($agro->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantity = round($dose * $surface, 2);
            }
            $amount = round($agro->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de insumos (opcionalmente por fruta)
    public function getTotalSupplies($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $supplies = \App\Models\Supply::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $supplies->isEmpty()) return 0;
        $supplyIds = $supplies->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('supply_id', $supplyIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $supply = $supplies[$item->supply_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $quantity = (($supply->unit_id == 4 && $supply->unit_id_price == 3) || ($supply->unit_id == 2 && $supply->unit_id_price == 1)) ? ($supply->quantity / 1000) : $supply->quantity;
            $quantity = round($quantity * $surface, 2);
            $amount = round($supply->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de servicios (opcionalmente por fruta)
    public function getTotalServices($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $services = \App\Models\Service::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $services->isEmpty()) return 0;
        $serviceIds = $services->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('service_items')
            ->select('service_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('service_id', $serviceIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $service = $services[$item->service_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $quantity = (($service->unit_id == 4 && $service->unit_id_price == 3) || ($service->unit_id == 2 && $service->unit_id_price == 1)) ? ($service->quantity / 1000) : $service->quantity;
            $quantity = round($quantity * $surface, 2);
            $amount = round($service->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    // Calcula el total global de cosechas (opcionalmente por fruta)
    public function getTotalHarvest($season_id, $team_id, $fruit_id = null)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($fruit_id) {
            $costCentersQuery->where('fruit_id', $fruit_id);
        }
        $costCenters = $costCentersQuery->get()->keyBy('id');
        $harvests = \App\Models\Harvest::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()->keyBy('id');
        if ($costCenters->isEmpty() || $harvests->isEmpty()) return 0;
        $harvestIds = $harvests->keys();
        $centerIds = $costCenters->keys();
        $items = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', DB::raw('COUNT(DISTINCT month_id) as months'))
            ->whereIn('harvest_id', $harvestIds)
            ->whereIn('cost_center_id', $centerIds)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id')
            ->get();
        $total = 0;
        foreach ($items as $item) {
            $harvest = $harvests[$item->harvest_id];
            $surface = $costCenters[$item->cost_center_id]->surface ?? 1;
            $quantity = (($harvest->unit_id == 4 && $harvest->unit_id_price == 3) || ($harvest->unit_id == 2 && $harvest->unit_id_price == 1)) ? ($harvest->quantity / 1000) : $harvest->quantity;
            $quantity = round($quantity * $surface, 2);
            $amount = round($harvest->price * $quantity, 2);
            $total += $amount * $item->months;
        }
        return $total;
    }

    /**
     * Obtiene el total de kilos estimados para un equipo y temporada,
     * usando el último estimate_status_id disponible.
     * El total de kilos corresponde a la suma de kilos_ha * surface de cada cost center relacionado.
     *
     * @param int $season_id
     * @param int $team_id
     * @return float
     */
    public function getTotalEstimatedKilos($season_id, $team_id)
    {
        // Obtener todas las estimaciones de la temporada/equipo
        $estimates = \App\Models\Estimate::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get();

        if ($estimates->isEmpty()) return [];


        // Agrupar estimates por fruta (vía estimate_status)
        $estimatesByFruit = [];
        foreach ($estimates as $estimate) {
            $estimateStatus = $estimate->estimateStatus;
            $fruitId = $estimateStatus ? $estimateStatus->fruit_id : null;
            if (!$fruitId) continue;
            if (!isset($estimatesByFruit[$fruitId])) {
                $estimatesByFruit[$fruitId] = [];
            }
            $estimatesByFruit[$fruitId][] = $estimate;
        }

        $kilosByFruit = [];
        $fruitIds = array_keys($estimatesByFruit);

        // Para cada fruta, tomar los estimates con el mayor estimate_status_id y sumar los kilos
        foreach ($estimatesByFruit as $fruitId => $estimatesGroup) {
            // Encontrar el último estimate_status_id para esta fruta
            $maxStatusId = collect($estimatesGroup)->max('estimate_status_id');
            // Filtrar los estimates con ese status
            $latestEstimates = collect($estimatesGroup)->where('estimate_status_id', $maxStatusId);
            $totalKilos = 0;
            foreach ($latestEstimates as $estimate) {
                $surface = $estimate->costCenter ? $estimate->costCenter->surface : 0;
                $kilos = ($estimate->kilos_ha ?? 0) * $surface;
                $totalKilos += $kilos;
            }
            $kilosByFruit[$fruitId] = $totalKilos;
        }

        // Obtener nombres de las frutas
        $fruitNames = [];
        if (!empty($fruitIds)) {
            $fruits = \App\Models\Fruit::whereIn('id', $fruitIds)->pluck('name', 'id');
            foreach ($fruits as $id => $name) {
                $fruitNames[$id] = $name;
            }
        }

        // Retornar ambos
        return [
            'kilosByFruit' => $kilosByFruit,
            'fruitNames' => $fruitNames
        ];
    }






    



}
