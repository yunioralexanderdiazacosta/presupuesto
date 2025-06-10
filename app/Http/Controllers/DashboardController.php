<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Budget;
use App\Models\Season;
use App\Models\CostCenter;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Supply;
use App\Models\Service;
use Inertia\Inertia;
use App\Services\WeatherService;

class DashboardController extends Controller
{
    public $month_id = '';

    public $totalAgrochemical = 0;

    public $totalFertilizer = 0;

    public $totalManPower = 0;

    public $totalSupplies = 0;

    public $totalServices = 0;

    public $monthsAgrochemical = [];

    public $monthsFertilizer = [];

    public $monthsManPower = [];

    public $monthsSupplies = [];

    public $monthsServices = [];

    public function __invoke(Request $request, WeatherService $weatherService)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
        $this->month_id = $season ? $season['month_id'] : 1;
        $months = array();
        $currentMonth = $this->month_id;
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $object = [
                'label' => $this->getMonthName($id),
                'value' =>  $id
            ];
            array_push($months, $object);
        }
        $costCenters = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });
        $costCentersId = $costCenters->pluck('value');
        $this->getAgrochemicalProducts($costCentersId);
        $this->getFertilizerProducts($costCentersId);
        $this->getManPowerProducts($costCentersId);
        $this->getServicesProducts($costCentersId);
        $this->getSuppliesProducts($costCentersId);
        $pieLabels = ['Agroquimicos', 'Fertilizantes', 'Mano de obra', 'Servicios', 'Insumos'];
        $pieDatasets = [
            [
                "data" => [round($this->totalAgrochemical), round($this->totalFertilizer), round($this->totalManPower), round($this->totalServices), round($this->totalSupplies)],
                "backgroundColor" => ['#36a2eb', '#ff6384', '#ffce56', '#008000', '#FF2C2C'],
                "hoverOffset" => 4,
                "cutout" => 0
            ]
        ];
        $totalSeason = number_format(($this->totalAgrochemical + $this->totalFertilizer + $this->totalManPower + $this->totalServices + $this->totalSupplies), 0, ',', '.');
        $totalAgrochemical = number_format($this->totalAgrochemical, 0, ',', '.');
        $totalFertilizer = number_format($this->totalFertilizer, 0, ',', '.');
        $totalManPower = number_format($this->totalManPower, 0, ',', '.');
        $totalServices = number_format($this->totalServices, 0, ',', '.');
        $totalSupplies = number_format($this->totalSupplies, 0, ',', '.');
        $monthsAgrochemical = [];
        foreach($this->monthsAgrochemical as $key => $value){
            $monthsAgrochemical[$key] = number_format($value, 0, ',','.');
        }
        $monthsFertilizer = [];
        foreach($this->monthsFertilizer as $key => $value){
            $monthsFertilizer[$key] = number_format($value, 0, ',','.');
        }
        $monthsManPower = [];
        foreach($this->monthsManPower as $key => $value){
            $monthsManPower[$key] = number_format($value, 0, ',','.');
        }
        $monthsServices = [];
        foreach($this->monthsServices as $key => $value){
            $monthsServices[$key] = number_format($value, 0, ',','.');
        }
        $monthsSupplies = [];
        foreach($this->monthsSupplies as $key => $value){
            $monthsSupplies[$key] = number_format($value, 0, ',','.');
        }
        // Weather integration
        $city = $request->input('city') ?? $request->input('weatherCity') ?? 'Curico, Chile'; // Usa la ciudad enviada por el frontend o la default
        $weather = $weatherService->getCurrentWeather($city);
        
        // Calcular totales de agroquímicos por estado de desarrollo
        $agrochemicalByDevState = [];
        $agrochemicals = Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->whereIn('ai.cost_center_id', $costCentersId)
            ->groupBy('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->get();
        foreach ($agrochemicals as $agrochemical) {
            $byDev = $this->getAgrochemicalResultByDevelopmentState($agrochemical, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($agrochemicalByDevState[$devStateId])) {
                    $agrochemicalByDevState[$devStateId] = 0;
                }
                $agrochemicalByDevState[$devStateId] += $amount;
            }
        }
        // Calcular gasto por hectárea de agroquímicos por estado de desarrollo
        $agrochemicalExpensePerHectare = [];
        foreach ($agrochemicals as $agrochemical) {
            $byDev = $this->getAgrochemicalExpensePerHectareByDevelopmentState($agrochemical, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($agrochemicalExpensePerHectare[$devStateId])) {
                    $agrochemicalExpensePerHectare[$devStateId] = 0;
                }
                $agrochemicalExpensePerHectare[$devStateId] += $amount;
            }
        }
        // Calcular totales de fertilizantes por estado de desarrollo
        $fertilizerByDevState = [];
        $fertilizers = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->whereIn('fi.cost_center_id', $costCentersId)
            ->groupBy('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->get();
        foreach ($fertilizers as $fertilizer) {
            $byDev = $this->getFertilizerResultByDevelopmentState($fertilizer, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($fertilizerByDevState[$devStateId])) {
                    $fertilizerByDevState[$devStateId] = 0;
                }
                $fertilizerByDevState[$devStateId] += $amount;
            }
        }
        // Calcular gasto por hectárea de fertilizantes por estado de desarrollo
        $fertilizerExpensePerHectare = [];
        foreach ($fertilizers as $fertilizer) {
            $byDev = $this->getFertilizerExpensePerHectareByDevelopmentState($fertilizer, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($fertilizerExpensePerHectare[$devStateId])) {
                    $fertilizerExpensePerHectare[$devStateId] = 0;
                }
                $fertilizerExpensePerHectare[$devStateId] += $amount;
            }
        }
        // Calcular totales de mano de obra por estado de desarrollo
        $manPowerByDevState = [];
        $manPowers = ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->select('mp.id', 'mp.price', 'mp.workday')
            ->whereIn('mpi.cost_center_id', $costCentersId)
            ->groupBy('mp.id', 'mp.price', 'mp.workday')
            ->get();
        foreach ($manPowers as $manPower) {
            $byDev = $this->getManPowerResultByDevelopmentState($manPower, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($manPowerByDevState[$devStateId])) {
                    $manPowerByDevState[$devStateId] = 0;
                }
                $manPowerByDevState[$devStateId] += $amount;
            }
        }
        // Calcular gasto por hectárea de mano de obra por estado de desarrollo
        $manPowerExpensePerHectare = [];
        foreach ($manPowers as $manPower) {
            $byDev = $this->getManPowerExpensePerHectareByDevelopmentState($manPower, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($manPowerExpensePerHectare[$devStateId])) {
                    $manPowerExpensePerHectare[$devStateId] = 0;
                }
                $manPowerExpensePerHectare[$devStateId] += $amount;
            }
        }
        // Calcular totales de servicios por estado de desarrollo
        $servicesByDevState = [];
        $services = Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->get();
        foreach ($services as $service) {
            $byDev = $this->getServiceResultByDevelopmentState($service, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($servicesByDevState[$devStateId])) {
                    $servicesByDevState[$devStateId] = 0;
                }
                $servicesByDevState[$devStateId] += $amount;
            }
        }
        // Calcular gasto por hectárea de servicios por estado de desarrollo
        $servicesExpensePerHectare = [];
        foreach ($services as $service) {
            $byDev = $this->getServiceExpensePerHectareByDevelopmentState($service, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($servicesExpensePerHectare[$devStateId])) {
                    $servicesExpensePerHectare[$devStateId] = 0;
                }
                $servicesExpensePerHectare[$devStateId] += $amount;
            }
        }
        // Calcular totales de insumos por estado de desarrollo
        $suppliesByDevState = [];
        $supplies = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->get();
        foreach ($supplies as $supply) {
            $byDev = $this->getSupplyResultByDevelopmentState($supply, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($suppliesByDevState[$devStateId])) {
                    $suppliesByDevState[$devStateId] = 0;
                }
                $suppliesByDevState[$devStateId] += $amount;
            }
        }
        // Calcular gasto por hectárea de insumos por estado de desarrollo
        $suppliesExpensePerHectare = [];
        foreach ($supplies as $supply) {
            $byDev = $this->getSupplyExpensePerHectareByDevelopmentState($supply, $costCentersId);
            foreach ($byDev as $devStateId => $amount) {
                if (!isset($suppliesExpensePerHectare[$devStateId])) {
                    $suppliesExpensePerHectare[$devStateId] = 0;
                }
                $suppliesExpensePerHectare[$devStateId] += $amount;
            }
        }
        // Obtener nombres de estados de desarrollo
        $devStates = \App\Models\DevelopmentState::all(['id', 'name'])->keyBy('id')->toArray();
        // Pasar ambos al frontend
        return Inertia::render('Dashboard', compact(
            'totalSeason', 'pieLabels', 'pieDatasets',
            'monthsAgrochemical', 'totalAgrochemical',
            'monthsFertilizer', 'totalFertilizer',
            'monthsManPower', 'totalManPower',
            'totalServices', 'monthsServices',
            'totalSupplies', 'monthsSupplies',
            'months', 'weather', 'city',
            'agrochemicalByDevState',
            'fertilizerByDevState',
            'manPowerByDevState',
            'servicesByDevState',
            'suppliesByDevState',
            'agrochemicalExpensePerHectare',
            'fertilizerExpensePerHectare',
            'manPowerExpensePerHectare',
            'servicesExpensePerHectare',
            'suppliesExpensePerHectare',
            'devStates' // <-- nombres de estados de desarrollo
        ));
    }

    private function getAgrochemicalProducts($costCentersId)
    {
        $products = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
        ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
        ->whereIn('ai.cost_center_id', $costCentersId)
        ->groupBy('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
        ->get()
        ->transform(function($value) use ($costCentersId){
            $data = $this->getAgrochemicalResult($value, $costCentersId);
            return [
                'id' => $value->id
            ];
        });

        return $products;
    } 

    private function getAgrochemicalResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;

        foreach($costCentersId as $costCenter){
           $first = CostCenter::select('surface')->where('id', $costCenter)->first();

           $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
           
            $surface = $first->surface;
            if($value->dose_type_id == 1){
                $quantityFirst = round($dose * $surface, 2);
            }elseif($value->dose_type_id == 2){
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            }
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('agrochemical_items')
                ->select('agrochemical_id')
                ->where('agrochemical_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                if(!isset($this->monthsAgrochemical[$month])){
                    $this->monthsAgrochemical[$month] = 0;    
                }
                $this->monthsAgrochemical[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalAgrochemical += $totalAmount; 
    }

     private function getFertilizerProducts($costCentersId)
    {
        $products = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
        ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
        ->whereIn('fi.cost_center_id', $costCentersId)
        ->groupBy('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
        ->get()
        ->transform(function($value) use ($costCentersId){
            $data = $this->getFertilizerResult($value, $costCentersId);
            return [
                'id' => $value->id
            ];
        });

        return $products;
    }

    private function getFertilizerResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach($costCentersId as $costCenter){
           $first = CostCenter::select('surface')->where('id', $costCenter)->first();
           
            $surface = $first->surface;
            $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') ||($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('fertilizer_items')
                ->select('fertilizer_id')
                ->where('fertilizer_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                if(!isset($this->monthsFertilizer[$month])){
                    $this->monthsFertilizer[$month] = 0;    
                }
                $this->monthsFertilizer[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalFertilizer += $totalAmount;
    }

     private function getManPowerProducts($costCentersId)
    {
        $products = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->leftJoin('units as u', 'mp.unit_id', 'u.id')
        ->select('mp.id', 'mp.price', 'mp.workday')
        ->whereIn('mpi.cost_center_id', $costCentersId)
        ->groupBy('mp.id', 'mp.price', 'mp.workday')
        ->get()
        ->transform(function($value) use ($costCentersId){
            $data = $this->getManPowerResult($value, $costCentersId);
            return [
                'id' => $value->id
            ];
        });

        return $products;
    }

    private function getManPowerResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach($costCentersId as $costCenter){
           $first = CostCenter::select('surface')->where('id', $costCenter)->first();

            $surface = $first->surface;
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('manpower_items')
                ->select('man_power_id')
                ->where('man_power_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                if(!isset($this->monthsManPower[$month])){
                    $this->monthsManPower[$month] = 0;    
                }
                $this->monthsManPower[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalManPower += $totalAmount;
    }


    private function getServicesProducts($costCentersId)
    {
        $products = Service::from('services as s')
        ->join('service_items as si', 's.id', 'si.service_id')
        ->leftJoin('units as u', 's.unit_id_price', 'u.id')
        ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price',  'u.name')
        ->whereIn('si.cost_center_id', $costCentersId)
        ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
        ->get()
        ->transform(function($value) use($costCentersId){
            $data = $this->getServicesResult($value, $costCentersId);
            return [
                'id'            => $value->id
            ];
        });

        return $products;
    }

    private function getServicesResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach($costCentersId as $costCenter){
           $first = CostCenter::select('surface')->where('id', $costCenter)->first();

           $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
           
            $surface = $first->surface;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('service_items')
                ->select('service_id')
                ->where('service_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                

                if(!isset($this->monthsServices[$month])){
                    $this->monthsServices[$month] = 0;    
                }
                $this->monthsServices[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalServices += $totalAmount; 
    }

    private function getSuppliesProducts($costCentersId)
    {
        $products = Supply::from('supplies as s')
        ->join('supply_items as si', 's.id', 'si.supply_id')
        ->leftJoin('units as u', 's.unit_id_price', 'u.id')
        ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price',  'u.name')
        ->whereIn('si.cost_center_id', $costCentersId)
        ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
        ->get()
        ->transform(function($value) use($costCentersId){
            $data = $this->getSuppliesResult($value, $costCentersId);
            return [
                'id'            => $value->id
            ];
        });

        return $products;
    }

    private function getSuppliesResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach($costCentersId as $costCenter){
           $first = CostCenter::select('surface')->where('id', $costCenter)->first();

           $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
           
            $surface = $first->surface;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('supply_items')
                ->select('supply_id')
                ->where('supply_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;

                if(!isset($this->monthsSupplies[$month])){
                    $this->monthsSupplies[$month] = 0;    
                }
                $this->monthsSupplies[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;

        }

        $this->totalSupplies += $totalAmount; 
    }

    /**
     * Obtiene el totalAmount de agroquímicos separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getAgrochemicalResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        // Obtener todos los cost centers con su development_state_id
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $surface = $center->surface;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('agrochemical_items')
                        ->select('agrochemical_id')
                        ->where('agrochemical_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de agroquímicos por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getAgrochemicalExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        // Obtener todos los cost centers con su development_state_id
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $surface = $center->surface;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('agrochemical_items')
                        ->select('agrochemical_id')
                        ->where('agrochemical_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            // Evitar división por cero
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de fertilizantes separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getFertilizerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        // Obtener todos los cost centers con su development_state_id
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('fertilizer_items')
                        ->select('fertilizer_id')
                        ->where('fertilizer_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de fertilizantes por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getFertilizerExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('fertilizer_items')
                        ->select('fertilizer_id')
                        ->where('fertilizer_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de mano de obra separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getManPowerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('manpower_items')
                        ->select('man_power_id')
                        ->where('man_power_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de mano de obra por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getManPowerExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('manpower_items')
                        ->select('man_power_id')
                        ->where('man_power_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de servicios separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getServiceResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('service_items')
                        ->select('service_id')
                        ->where('service_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de servicios por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getServiceExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('service_items')
                        ->select('service_id')
                        ->where('service_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de insumos separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getSupplyResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('supply_items')
                        ->select('supply_id')
                        ->where('supply_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de insumos por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getSupplyExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = \DB::table('supply_items')
                        ->select('supply_id')
                        ->where('supply_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }

    public function getMonthName($id)
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        return $months[$id];
    }
}




