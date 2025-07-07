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
use App\Models\Supply;
use App\Models\Service;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Agrochemical;
use App\Models\DoseType;
use Inertia\Inertia;
use App\Http\Controllers\Traits\BudgetTotalsTrait;

class SuppliesController extends Controller
{
    use BudgetTotalsTrait;

/**
     * Suma el total de administración para los cost centers y temporada dados.
     */
    private function getTotalAdministration($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $administrations = \App\Models\Administration::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get();

        $total = 0;
        foreach ($administrations as $adm) {
            // Buscar los meses activos en los que aparece este administration_id
            $activeMonths = DB::table('administration_items')
                ->where('administration_id', $adm->id)
                ->whereIn('month_id', $months)
                ->distinct('month_id')
                ->pluck('month_id');
            $countMonths = $activeMonths->count();
            if ($countMonths > 0) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity * $countMonths, 2);
                $total += $amount;
            }
        }
        return $total;
    }


/**
  
    
     * Suma el total de administración para los cost centers y temporada dados.
     */
    private function getTotalField($season_id, $team_id)
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
            // Buscar los meses activos en los que aparece este field_id
            $activeMonths = DB::table('field_items')
                ->where('field_id', $field->id)
                ->whereIn('month_id', $months)
                ->distinct('month_id')
                ->pluck('month_id');
            $countMonths = $activeMonths->count();
            if ($countMonths > 0) {
                $quantity = ($field->quantity !== null && ($field->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($field->quantity / 1000) : $field->quantity) : 0;
                $amount = round($field->price * $quantity * $countMonths, 2);
                $total += $amount;
            }
        }
        return $total;
    }

    public $month_id = '';

    public $totalData1 = 0;

    public $totalData2 = 0;

    public $totalFertilizer = 0;

    public $totalAgrochemical = 0;

    public $totalManPower = 0;

    public $totalServices = 0;

    public function __invoke()
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
        $this->month_id = $season['month_id'];

        $subfamilies = Level3::from('level3s as l3')
            ->join('level2s as l2', 'l2.id', 'l3.level2_id')
            ->join('level1s as l1', 'l1.id', 'l2.level1_id')
            ->select('l3.id', 'l3.name')
            ->where('l1.team_id', $user->team_id)
            ->where('l2.name', 'insumos')
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

        $months = [];
        $currentMonth = $this->month_id;
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $object = [
                'label' => $this->getMonthName($id),
                'value' =>  $id
            ];
            $months[] = $object;
        }

        $costCenters = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });

        $supplies = Supply::with('subfamily:id,name', 'unit:id,name', 'unit2:id,name', 'items:id')
            ->whereHas('items', function($query) use ($costCenters){
                $query->whereIn('cost_center_id', $costCenters->pluck('value'));
            })
            ->paginate(10)
            ->through(function($supply){
                $items = $supply->items->pluck('pivot');
                $months = array_column($items->toArray(), 'month_id');
                $cc = array_column($items->toArray(), 'cost_center_id');
                return [
                    'id'            => $supply->id,
                    'product_name'  => $supply->product_name,
                    'quantity'      => $supply->quantity,
                    'subfamily_id'  => $supply->subfamily_id,
                    'unit_id'       => $supply->unit_id,
                    'unit_id_price' => $supply->unit_id_price,
                    'observations'  => $supply->observations,
                    'subfamily'     => $supply->subfamily,
                    'unit'          => $supply->unit,
                    'unit2'         => $supply->unit2,
                    'price'         => $supply->price,
                    'months'        => array_unique($months),
                    'cc'            => array_values(array_unique($cc))
                ];
            });

        $data = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('cost_centers as cc', 'si.cost_center_id', 'cc.id')
            ->select('si.cost_center_id', 'cc.name', 'cc.surface','cc.variety_id')
            ->whereIn('si.cost_center_id', $costCenters->pluck('value'))
            ->groupBy('si.cost_center_id', 'cc.name', 'cc.surface','cc.variety_id')
            ->get()
            ->transform(function($value) use ($costCenters){
                return [
                    'id' => $value->cost_center_id,
                    'name' => $value->name,
                    'variety_id' => $value->variety_id,
                    'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface),
                    'total' => $this->getTotal($value->cost_center_id)
                ];
            });

        $data3 = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('cost_centers as cc', 'si.cost_center_id', 'cc.id')
            ->select('si.cost_center_id', 'cc.name', 'cc.surface','cc.variety_id')
            ->whereIn('si.cost_center_id', $costCenters->pluck('value'))
            ->groupBy('si.cost_center_id', 'cc.name', 'cc.surface','cc.variety_id')
            ->get()
            ->transform(function($value) use ($costCenters){
                return [
                    'id' => $value->cost_center_id,
                    'name' => $value->name,
                    'variety_id' => $value->variety_id,
                    'subfamilies' => $this->getSubfamilies($value->cost_center_id, null, true),
                    'total' => $this->getTotal($value->cost_center_id)
                ];
            });

        $costCentersId = $costCenters->pluck('value');

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

        $data2 = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->where('l2.name', 'insumos')
            ->select('l3.id', 'l3.name')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('l3.id', 'l3.name')
            ->get()
            ->transform(function($subfamily) use ($costCentersId){
                return [
                    'id' => $subfamily->id,
                    'name' => $subfamily->name,
                    'products' => $this->getProducts2($subfamily->id, $costCentersId)
                ];
            });

        // Calcular totales usando el trait
        $totalSupplies = $this->getTotalSupplies($season_id, $user->team_id);
        $totalFertilizer = $this->getTotalFertilizer($season_id, $user->team_id);
        $totalManPower = $this->getTotalManPower($season_id, $user->team_id);
        $totalAgrochemical = $this->getTotalAgrochemical($season_id, $user->team_id);
        $totalServices = $this->getTotalServices($season_id, $user->team_id);
        $totalAdministration = $this->getTotalAdministration($season_id, $user->team_id);
        $totalField = $this->getTotalField($season_id, $user->team_id);

        $totalAbsolute = $totalSupplies + $totalFertilizer + $totalManPower + $totalAgrochemical + $totalServices + $totalAdministration + $totalField;
        $percentage = $totalAbsolute > 0 ? round(($totalSupplies / $totalAbsolute) * 100, 2) : 0;

        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($this->totalData2, 0, ',', '.');

        return Inertia::render('Supplies', [
            'units' => $units,
            'subfamilies' => $subfamilies,
            'months' => $months,
            'costCenters' => $costCenters,
            'supplies' => $supplies,
            'data' => $data,
            'data2' => $data2,
            'data3' => $data3,
            'season' => $season,
            'totalData1' => $totalData1,
            'totalData2' => $totalData2,
            'percentage' => $percentage,
            'varieties' => $varieties,
            'fruits' => $fruits,
        ]);
    }

    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = Supply::from('supplies as s')
        ->join('supply_items as si', 's.id', 'si.supply_id')
        ->join('level3s as l3', 's.subfamily_id', 'l3.id')
        ->join('level2s as l2', 'l3.level2_id', 'l2.id')
        ->where('l2.name', 'insumos')
        ->select('l3.id', 'l3.name')
        ->where('si.cost_center_id', $costCenterId)
        ->groupBy('l3.id', 'l3.name')
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
        $products = Supply::from('supplies as s')
        ->join('supply_items as si', 's.id', 'si.supply_id')
        ->leftJoin('units as u', 's.unit_id_price', 'u.id')
        ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price',  'u.name')
        ->where('si.cost_center_id', $costCenterId)
        ->where('s.subfamily_id', $subfamilyId)
        ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
        ->get()
        ->transform(function($value) use ($surface, $bills){

            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity; 

            $quantityFirst = $bills == true ? round($quantity, 2) : round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
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
        $total = DB::table('supply_items')
        ->select('supply_id')
        ->where('cost_center_id', $costCenterId)
        ->distinct('supply_id')
        ->count();

        return $total;
    }

    private function getMonths($supplyId, $quantity, $amount, $bills)
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
            $count = DB::table('supply_items')
            ->select('supply_id')
            ->where('supply_id', $supplyId)
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
        $products = Supply::from('supplies as s')
        ->join('supply_items as si', 's.id', 'si.supply_id')
        ->leftJoin('units as u', 's.unit_id_price', 'u.id')
        ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
        ->whereIn('si.cost_center_id', $costCentersId)
        ->where('s.subfamily_id', $subfamilyId)
        ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
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

        $this->totalData2 += $totalAmount;

        return [
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ]; 
    }

  
}