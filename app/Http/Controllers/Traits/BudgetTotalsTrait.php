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
            ->get();
        $total = 0;
        foreach ($fields as $field) {
            $activeMonths = DB::table('field_items')
                ->where('field_id', $field->id)
                ->whereIn('month_id', $months)
                ->distinct('month_id')
                ->pluck('month_id');
            $countMonths = $activeMonths->count();
            if ($countMonths > 0) {
                $quantity = ($field->quantity !== null && ($field->quantity > 0)) ? ((in_array($field->unit_id ?? null, [2,4])) ? ($field->quantity / 1000) : $field->quantity) : 0;
                $amount = round($field->price * $quantity * $countMonths, 2);
                $total += $amount;
            }
        }
        return $total;
    }

    // Calcula el total global de administración
    public function getTotalAdministration($season_id, $team_id)
    {
        $total = 0;
        $administrations = \App\Models\Administration::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get();
        foreach ($administrations as $adm) {
            $quantity = (($adm->unit_id == 4) || ($adm->unit_id == 2)) ? ($adm->quantity / 1000) : $adm->quantity;
            $amount = round($adm->price * $quantity, 2);
            $months = DB::table('administration_items')
                ->where('administration_id', $adm->id)
                ->distinct('month_id')
                ->pluck('month_id');
            $total += $amount * count($months);
        }
        return $total;
    }

    // Calcula el total global de fertilizantes
    public function getTotalFertilizer($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $fertilizers = \App\Models\Fertilizer::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($fertilizers as $fert) {
                $dose = (($fert->unit_id == 4 && $fert->unit_id_price == 3) || ($fert->unit_id == 2 && $fert->unit_id_price == 1)) ? ($fert->dose / 1000) : $fert->dose;
                $quantity = round($dose * $surface, 2);
                $amount = round($fert->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('fertilizer_items')
                        ->where('fertilizer_id', $fert->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    // Calcula el total global de mano de obra
    public function getTotalManPower($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $manpowers = \App\Models\ManPower::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($manpowers as $mp) {
                $quantity = round($mp->workday * $surface, 2);
                $amount = round($mp->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('manpower_items')
                        ->where('man_power_id', $mp->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    // Calcula el total global de agroquímicos
    public function getTotalAgrochemical($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $agrochemicals = \App\Models\Agrochemical::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($agrochemicals as $agro) {
                $dose = (($agro->unit_id == 4 && $agro->unit_id_price == 3) || ($agro->unit_id == 2 && $agro->unit_id_price == 1)) ? ($agro->dose / 1000) : $agro->dose;
                if ($agro->dose_type_id == 1) {
                    $quantity = round($dose * $surface, 2);
                } elseif ($agro->dose_type_id == 2) {
                    $quantity = round((($agro->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantity = round($dose * $surface, 2);
                }
                $amount = round($agro->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('agrochemical_items')
                        ->where('agrochemical_id', $agro->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    // Calcula el total global de insumos
    public function getTotalSupplies($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $supplies = \App\Models\Supply::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($supplies as $supply) {
                $quantity = (($supply->unit_id == 4 && $supply->unit_id_price == 3) || ($supply->unit_id == 2 && $supply->unit_id_price == 1)) ? ($supply->quantity / 1000) : $supply->quantity;
                $quantity = round($quantity * $surface, 2);
                $amount = round($supply->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('supply_items')
                        ->where('supply_id', $supply->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    // Calcula el total global de servicios
    public function getTotalServices($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $services = \App\Models\Service::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($services as $service) {
                $quantity = (($service->unit_id == 4 && $service->unit_id_price == 3) || ($service->unit_id == 2 && $service->unit_id_price == 1)) ? ($service->quantity / 1000) : $service->quantity;
                $quantity = round($quantity * $surface, 2);
                $amount = round($service->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('service_items')
                        ->where('service_id', $service->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }
}
