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
use App\Models\Level3;
use App\Models\CostCenter;
use App\Models\Month;
use Inertia\Inertia;

use App\Http\Controllers\Traits\BudgetTotalsTrait;

class ManPowersController extends Controller
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

    public $totalAgrochemical = 0;

    public $totalFertilizer = 0;

    public $totalSupplies = 0;

    public $totalServices = 0;
    public $totalManPower = 0;
    public $totalAdministration = 0;
    public $totalField = 0;
    public $totalAbsolute = 0;
    public $percentageManPower = 0;


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
        ->where('l2.name', 'mano de obra')
        ->where('season_id', $season_id)
        ->get()->transform(function($subfamily){
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

        $costCenters = CostCenter::select('id', 'name', 'variety_id')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id,
                'variety_id' => $costCenter->variety_id
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

        // --- AÑADIR variety_id a data y data3 para filtrado ---
        $data = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
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

        $data3 = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
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

        // --- VARIEDADES Y FRUTAS ---
        $varieties = \App\Models\Variety::whereIn('id',
            \App\Models\CostCenter::where('season_id', $season_id)
                ->whereNotNull('variety_id')
                ->pluck('variety_id')
                ->unique()
        )
        ->select('id', 'name', 'fruit_id')
        ->orderBy('name')
        ->get();

        $fruits = \App\Models\Fruit::whereIn('id', $varieties->pluck('fruit_id')->unique()->filter())->orderBy('name')->get(['id', 'name']);

        $costCentersId = $costCenters->pluck('value');

        $data2 = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('level3s as s', 'mp.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('l2.name', 'mano de obra')
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




       // Calcular totales globales de cada rubro usando el trait
        $team_id = $user->team_id;
        $this->totalAgrochemical   = $this->getTotalAgrochemical($season_id, $team_id);
        $this->totalFertilizer     = $this->getTotalFertilizer($season_id, $team_id);
        $this->totalManPower       = $this->getTotalManPower($season_id, $team_id);
        $this->totalSupplies       = $this->getTotalSupplies($season_id, $team_id);
        $this->totalServices       = $this->getTotalServices($season_id, $team_id);
        $this->totalAdministration = $this->getTotalAdministration($season_id, $team_id);
        $this->totalField          = $this->getTotalField($season_id, $team_id);

        // Sumar todos los rubros para el total absoluto
        $this->totalAbsolute = round($this->totalAgrochemical)
            + round($this->totalFertilizer)
            + round($this->totalManPower)
            + round($this->totalSupplies)
            + round($this->totalServices)
            + round($this->totalAdministration)
            + round($this->totalField);


  // Calcular el porcentaje de agroquímicos sobre el total absoluto
        $this->percentageManPower = $this->totalAbsolute > 0
            ? round((round($this->totalManPower) / $this->totalAbsolute) * 100, 2)
            : 0;


        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($this->totalData2, 0, ',', '.');

      // Variables locales para compact()
        $totalAgrochemical = $this->totalAgrochemical;
        $totalFertilizer = $this->totalFertilizer;
        $totalManPower = $this->totalManPower;
        $totalSupplies = $this->totalSupplies;
        $totalServices = $this->totalServices;
        $totalAdministration = $this->totalAdministration;
        $totalField = $this->totalField;
        $totalAbsolute = $this->totalAbsolute;
        $percentageManPower = $this->percentageManPower;




        return Inertia::render('ManPowers', compact('subfamilies', 'months', 'costCenters', 'manPowers', 'season', 'data', 'data2', 'data3', 'totalData1', 'totalData2', 
        'totalAgrochemical', 'totalFertilizer', 'totalManPower', 'totalSupplies', 'totalServices', 'totalAdministration', 'totalField', 'totalAbsolute',
            'percentageManPower',
            'varieties', 'fruits'));
    }








    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('level3s as s', 'mp.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('l2.name', 'mano de obra')
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


  
   

    

    
}