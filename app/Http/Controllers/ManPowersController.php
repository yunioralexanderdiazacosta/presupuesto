<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Season;
use App\Models\ManPower;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\Supply;
use App\Models\Service;
use App\Models\Subfamily;
use App\Models\CostCenter;
use App\Models\Month;
use Inertia\Inertia;

class ManPowersController extends Controller
{
    public $month_id = '';

    public $totalData1 = 0;

    public $totalData2 = 0;

    public $totalAgrochemical = 0;

    public $totalFertilizer = 0;

    public $totalSupplies = 0;

    public $totalServices = 0;

    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];

        $subfamilies = Subfamily::where('id_form', 3)->get()->transform(function($subfamily){
            return [
                'label' => $subfamily->name,
                'value' => $subfamily->id
            ];
        });

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

        $manPowers = ManPower::with('subfamily:id,name', 'items:id')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->paginate(10)->through(function($manPower){
            $items = $manPower->items->pluck('pivot');
            $months = array_column($items->toArray(), 'month_id');
            $cc = array_column($items->toArray(), 'cost_center_id');
            return [
                'id'            => $manPower->id,
                'product_name'  => $manPower->product_name,
                'workday'       => $manPower->workday,
                'price'         => $manPower->price,
                'subfamily_id'  => $manPower->subfamily_id,
                'observations'  => $manPower->observations,
                'subfamily'     => $manPower->subfamily,
                'price'         => $manPower->price,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });


        $data = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });

        $data3 = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, null, true),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });



        $costCentersId = $costCenters->pluck('value');

        $data2 = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('subfamilies as s', 'mp.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->whereIn('mpi.cost_center_id', $costCentersId)
        ->groupBy('s.id', 's.name')
        ->get()
        ->transform(function($subfamily) use ($costCentersId){
            return [
                'id' => $subfamily->id,
                'name' => $subfamily->name,
                'products' => $this->getProducts2($subfamily->id, $costCentersId)
            ];
        });

        $this->getAgrochemicalProducts($costCentersId);
        $this->getFertilizerProducts($costCentersId);
        $this->getSuppliesProducts($costCentersId);
        $this->getServicesProducts($costCentersId);

        $totalAbsolute = round($this->totalData2) + round($this->totalAgrochemical) + round($this->totalFertilizer) + round($this->totalSupplies) + round($this->totalServices);

        $percentage = $totalAbsolute > 0 ? round(((round($this->totalData2) / $totalAbsolute) * 100), 2) : 0;


        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($this->totalData2, 0, ',', '.');

        return Inertia::render('ManPowers', compact('subfamilies', 'months', 'costCenters', 'manPowers', 'season', 'data', 'data2', 'data3', 'totalData1', 'totalData2', 'percentage'));
    }

    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('subfamilies as s', 'mp.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->where('mpi.cost_center_id', $costCenterId)
        ->groupBy('s.id', 's.name')
        ->get()
        ->transform(function($subfamily) use ($costCenterId, $surface, $bills){
            return [
                'id' => $subfamily->id,
                'name' => $subfamily->name,
                'products' => $this->getProducts($subfamily->id, $costCenterId, $surface, $bills)
            ];
        });

        return $subfamilies;
    }

    private function getProducts($subfamilyId, $costCenterId, $surface, $bills)
    {
        $products = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->leftJoin('units as u', 'mp.unit_id', 'u.id')
        ->select('mp.id', 'mp.product_name', 'mp.price', 'mp.workday', 'u.name')
        ->where('mpi.cost_center_id', $costCenterId)
        ->where('mp.subfamily_id', $subfamilyId)
        ->groupBy('mp.id', 'mp.product_name', 'mp.price', 'mp.workday', 'u.name')
        ->get()
        ->transform(function($value) use ($surface, $bills){
            $quantityFirst = $bills == true ? round($value->workday, 2) : round(($value->workday * $surface), 2);
            $amountFirst = round(($value->price * $quantityFirst), 2);
            $data = $this->getMonths($value->id, $quantityFirst, $amountFirst, $bills);

            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->name ?? '',
                'totalQuantity' => $data['totalQuantity'],
                'totalAmount'   => $data['totalAmount'],
                'months'        => $data['months']
            ];
        });

        return $products;
    }

    private function getTotal($costCenterId)
    {
        $total = DB::table('manpower_items')
        ->select('man_power_id')
        ->where('cost_center_id', $costCenterId)
        ->distinct('man_power_id')
        ->count();

        return $total;
    }

    private function getMonths($manPowerId, $quantity, $amount, $bills)
    {
        $data = array();
        $currentMonth = $this->month_id;

        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            array_push($data, $id);
        }

        $months = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach($data as $month)
        {
            $count = DB::table('manpower_items')
            ->select('man_power_id')
            ->where('man_power_id', $manPowerId)
            ->where('month_id', $month)
            ->count();

            $amountMonth = $count > 0 ? $amount : 0;
            $quantityMonth = $count > 0 ? $quantity : 0;
            $totalAmount += $amountMonth;
            $totalQuantity += $quantityMonth;
            array_push($months, number_format($amountMonth, 0, '', '.'));        
        }

        if($bills == false){
            $this->totalData1 += $totalAmount;
        }

        return [
            'months' => $months,
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ];
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

     private function getProducts2($subfamilyId, $costCentersId)
    {
        $products = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->leftJoin('units as u', 'mp.unit_id', 'u.id')
        ->select('mp.id', 'mp.product_name', 'mp.price', 'mp.workday', 'u.name')
        ->whereIn('mpi.cost_center_id', $costCentersId)
        ->where('mp.subfamily_id', $subfamilyId)
        ->groupBy('mp.id', 'mp.product_name', 'mp.price', 'mp.workday', 'u.name')
        ->get()
        ->transform(function($value) use ($costCentersId){
            $data = $this->getResult2($value, $costCentersId);
            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->name ?? '',
                'totalQuantity' => $data['totalQuantity'],
                'totalAmount'   => $data['totalAmount'],
            ];
        });

        return $products;
    }

    private function getResult2($value, $costCentersId)
    {
        $totalAmount = 0;
        $totalQuantity = 0;
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
            $totalQuantityCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('manpower_items')
                ->select('man_power_id')
                ->where('man_power_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $quantityMonth = $count > 0 ? $quantityFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                $totalQuantityCostCenter += $quantityMonth;
            }
            $totalAmount += $totalAmountCostCenter;
            $totalQuantity += $totalQuantityCostCenter;
        }

        $this->totalData2 += $totalAmount;

        return [
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ];
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
                'id'            => $value->id
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
            $data = $this->getFertilizerResult2($value, $costCentersId);
            return [
                'id' => $value->id
            ];
        });

        return $products;
    }

     private function getFertilizerResult2($value, $costCentersId)
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
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalFertilizer += $totalAmount;
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
        $totalQuantity = 0;
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
            $totalQuantityCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('supply_items')
                ->select('supply_id')
                ->where('supply_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $quantityMonth = $count > 0 ? $quantityFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                $totalQuantityCostCenter += $quantityMonth;
            }
            $totalAmount += $totalAmountCostCenter;
            $totalQuantity += $totalQuantityCostCenter;
        }

        $this->totalSupplies += $totalAmount; 
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
        $totalQuantity = 0;
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
            $totalQuantityCostCenter = 0;
            foreach($data as $month)
            {
                $count = DB::table('service_items')
                ->select('service_id')
                ->where('service_id', $value->id)
                ->where('month_id', $month)
                ->where('cost_center_id', $costCenter)
                ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $quantityMonth = $count > 0 ? $quantityFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                $totalQuantityCostCenter += $quantityMonth;
            }
            $totalAmount += $totalAmountCostCenter;
            $totalQuantity += $totalQuantityCostCenter;
        }

        $this->totalServices += $totalAmount; 
    }
}