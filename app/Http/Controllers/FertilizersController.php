<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Season;
use App\Models\Level3;
use App\Models\CostCenter;
use App\Models\Unit;
use App\Models\Month;
use App\Models\Fertilizer;
use App\Models\Agrochemical;
use App\Models\ManPower;
use App\Models\Supply;
use App\Models\Service;
use Inertia\Inertia;


class FertilizersController extends Controller
{
    public $month_id = '';

    public $totalData1 = 0;

    public $totalData2 = 0;

    public $totalAgrochemical = 0;

    public $totalManPower = 0;

    public $totalSupplies = 0;

    public $totalServices = 0;

    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];

        $subfamilies =  Level3::from('level3s as l3')
        ->join('level2s as l2', 'l2.id', 'l3.level2_id')
        ->join('level1s as l1', 'l1.id', 'l2.level1_id')
        ->select('l3.id', 'l3.name')
        ->where('l1.team_id', $user->team_id)
        ->where('l2.name', 'fertilizantes')
        ->where('season_id', $season_id)
        ->get()->transform(function($subfamily){
            return [
                'label' => $subfamily->name, 
                'value' => $subfamily->id
            ];
        });

        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
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

        $fertilizers = Fertilizer::with('subfamily:id,name', 'unit:id,name', 'items:id', 'unit2:id,name')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->paginate(10)->through(function($fertilizer){
            $items = $fertilizer->items->pluck('pivot');
            $months = array_column($items->toArray(), 'month_id');
            $cc = array_column($items->toArray(), 'cost_center_id');
            return [
                'id'            => $fertilizer->id,
                'product_name'  => $fertilizer->product_name,
                'dose'          => $fertilizer->dose,
                'price'         => $fertilizer->price,
                'subfamily_id'  => $fertilizer->subfamily_id,
                'unit_id'       => $fertilizer->unit_id,
                'unit_id_price' => $fertilizer->unit_id_price,
                'observations'  => $fertilizer->observations,
                'subfamily'     => $fertilizer->subfamily,
                'unit'          => $fertilizer->unit,
                'unit2'     => $fertilizer->unit2,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });


        $data = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('cost_centers as cc', 'fi.cost_center_id', 'cc.id')
        ->select('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // Add variety_id
        ->whereIn('fi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // Add variety_id
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id, // Add variety_id
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });

        $data3 = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('cost_centers as cc', 'fi.cost_center_id', 'cc.id')
        ->select('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // Add variety_id
        ->whereIn('fi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // Add variety_id
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id, // Add variety_id
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, null, true),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });
        // Obtener variedades asociadas a los cost centers de este equipo y temporada
        $varieties = \App\Models\Variety::whereIn('id',
            \App\Models\CostCenter::where('season_id', $season_id)
                ->whereHas('season.team', function($query) use ($user){
                    $query->where('team_id', $user->team_id);
                })
                ->whereNotNull('variety_id')
                ->pluck('variety_id')
                ->unique()
        )
        ->select('id', 'name', 'fruit_id')
        ->orderBy('name')
        ->get();

        // Obtener frutas asociadas a las variedades filtradas
        $fruits = \App\Models\Fruit::whereIn('id', $varieties->pluck('fruit_id')->unique()->filter())->orderBy('name')->get(['id', 'name']);

        $costCentersId = $costCenters->pluck('value');

        $data2 = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('level3s as s', 'f.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->whereIn('fi.cost_center_id', $costCentersId)
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
        $this->getManPowerProducts($costCentersId);
        $this->getSuppliesProducts($costCentersId);
        $this->getServicesProducts($costCentersId);

        $totalAbsolute = round($this->totalData2) + round($this->totalAgrochemical) + round($this->totalManPower) + round($this->totalSupplies) + round($this->totalServices);

        $percentage = $totalAbsolute > 0 ? round(((round($this->totalData2) / $totalAbsolute) * 100), 2) : 0;

        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($this->totalData2, 0, ',', '.');

        return Inertia::render('Fertilizers', compact('units', 'subfamilies', 'months', 'costCenters', 'fertilizers', 'season', 'data', 'data2', 'data3', 'totalData1', 'totalData2', 'percentage', 'varieties', 'fruits'));
    }

    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('level3s as s', 'f.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->where('fi.cost_center_id', $costCenterId)
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
        $products = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
        ->select('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
        ->where('fi.cost_center_id', $costCenterId)
        ->where('f.subfamily_id', $subfamilyId)
        ->groupBy('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
        ->get()
        ->transform(function($value) use ($surface, $bills){
            $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') ||($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = $bills == true ? round($dose, 2) : round(($dose * $surface), 2);
            $amountFirst = round(($value->price * $quantityFirst), 2);
            $data = $this->getMonths($value->id, $quantityFirst, $amountFirst, $bills); 

            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->name,
                'totalQuantity' => $data['totalQuantity'],
                'totalAmount'   => $data['totalAmount'],
                'months'        => $data['months']
            ];
        });

        return $products;
    }

    private function getTotal($costCenterId)
    {   
        $total = DB::table('fertilizer_items')
        ->select('fertilizer_id')
        ->where('cost_center_id', $costCenterId)
        ->distinct('fertilizer_id')
        ->count();

        return $total;
    }

    private function getMonths($fertilizerId, $quantity, $amount, $bills)
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
            $count = DB::table('fertilizer_items')
            ->select('fertilizer_id')
            ->where('fertilizer_id', $fertilizerId)
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
        $products = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
        ->select('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
        ->whereIn('fi.cost_center_id', $costCentersId)
        ->where('f.subfamily_id', $subfamilyId)
        ->groupBy('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
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
            $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') ||($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
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
                $count = DB::table('fertilizer_items')
                ->select('fertilizer_id')
                ->where('fertilizer_id', $value->id)
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
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalManPower += $totalAmount;
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