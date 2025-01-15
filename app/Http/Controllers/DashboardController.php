<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Budget;
use App\Models\CostCenter;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\ManPower;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public $month_id = '';

    public $totalAgrochemical = 0;

    public $totalFertilizer = 0;

    public $totalManPower = 0;

    public $monthsAgrochemical = [];

    public $monthsFertilizer = [];

    public $monthsManPower = [];

    public function __invoke()
    {
        $user = Auth::user();

        $budget_id = session('budget_id');

        $budget = Budget::select('name', 'month_id')->where('id', $budget_id)->first();

        $this->month_id = $budget ? $budget['month_id'] : 1;

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

        $costCenters = CostCenter::select('id', 'name')->where('budget_id', $budget_id)->whereHas('budget.team', function($query) use ($user){
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

        $pieLabels = ['Agroquimicos', 'Fertilizantes', 'Mano de obra'];

        $pieDatasets = [
            [
                "data" => [round($this->totalAgrochemical), round($this->totalFertilizer), round($this->totalManPower)],
                "backgroundColor" => ['#36a2eb', '#ff6384', '#ffce56'],
                "hoverOffset" => 4,
                "cutout" => 0
            ]
        ];

        $totalBudget = number_format(($this->totalAgrochemical + $this->totalFertilizer + $this->totalManPower), 0, ',', '.');

        $totalAgrochemical = number_format($this->totalAgrochemical, 0, ',', '.');
        $totalFertilizer = number_format($this->totalFertilizer, 0, ',', '.');
        $totalManPower = number_format($this->totalManPower, 0, ',', '.');

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
        
        return Inertia::render('Dashboard', compact('totalBudget', 'pieLabels', 'pieDatasets', 'monthsAgrochemical', 'totalAgrochemical', 'monthsFertilizer', 'totalFertilizer', 'monthsManPower', 'totalManPower', 'months'));
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
