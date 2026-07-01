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
use App\Models\CompanyReason;
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
public $totalHarvest = 0;
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

        $costCenters = CostCenter::select('id', 'name', 'variety_id', 'company_reason_id')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id,
                'variety_id' => $costCenter->variety_id,
                'company_reason_id' => $costCenter->company_reason_id,
            ];
        });

        $companyReasons = CompanyReason::whereIn(
            'id',
            CostCenter::where('season_id', $season_id)
                ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
                ->whereNotNull('company_reason_id')
                ->pluck('company_reason_id')
        )
        ->orderBy('name')
        ->get(['id', 'name'])
        ->map(fn($cr) => ['value' => $cr->id, 'label' => $cr->name])
        ->values();

        $manPowers = ManPower::with('subfamily:id,name', 'items:id', 'user:id,name')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->orderBy('id')->paginate(10)->through(function($manPower){
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
                'cc'            => array_values(array_unique($cc)),
                'user'          => $manPower->user ? ['name' => $manPower->user->name] : null

            ];
        });

        // --- AÑADIR variety_id a data y data3 para filtrado ---
        $data = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id', 'cc.company_reason_id')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id', 'cc.company_reason_id')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id,
                'company_reason_id' => $value->company_reason_id,
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });

        $data3 = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('cost_centers as cc', 'mpi.cost_center_id', 'cc.id')
        ->select('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id', 'cc.company_reason_id')
        ->whereIn('mpi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('mpi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id', 'cc.company_reason_id')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id,
                'company_reason_id' => $value->company_reason_id,
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
  
  
        $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($season_id, $user) {
            $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $season_id);
        }])
        ->where('season_id', $season_id)
        ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
        ->get()
        ->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'cost_centers' => $g->costCenters->map(fn($cc) => [
                'id' => $cc->id,
                'name' => $cc->name
            ])->values(),
        ]);




        $costCentersId = $costCenters->pluck('value');

        $data4 = $this->buildData4($costCentersId, $season_id, $user->team_id);

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
        $this->totalHarvest       = $this->getTotalHarvest($season_id, $team_id);

        // Sumar todos los rubros para el total absoluto
        $this->totalAbsolute = round($this->totalAgrochemical)
            + round($this->totalFertilizer)
            + round($this->totalManPower)
            + round($this->totalSupplies)
            + round($this->totalServices)
            + round($this->totalAdministration)
            + round($this->totalField)
            + round($this->totalHarvest);

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
        $totalHarvest = $this->totalHarvest;
        $totalAbsolute = $this->totalAbsolute;
        $percentageManPower = $this->percentageManPower;




        return Inertia::render('ManPowers', compact('subfamilies', 'months', 'costCenters', 'companyReasons', 'groupings', 'manPowers', 'season', 'data', 'data2', 'data3', 'data4', 'totalData1', 'totalData2',
        'totalAgrochemical', 'totalFertilizer', 'totalManPower', 'totalSupplies', 'totalServices', 'totalHarvest', 'totalAdministration', 'totalField', 'totalAbsolute',
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
            ->get();

        // Precalcular los meses activos para todos los productos de este cost center
        $productIds = $products->pluck('id')->all();
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }
        $activeMonthsByProduct = DB::table('manpower_items')
            ->select('man_power_id', 'month_id')
            ->where('cost_center_id', $costCenterId)
            ->whereIn('man_power_id', $productIds)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy('man_power_id');

        $result = $products->map(function($value) use ($surface, $bills, $activeMonthsByProduct, $monthsRange) {
            $quantityFirst = $bills == true ? round($value->workday, 2) : round(($value->workday * $surface), 2);
            $amountFirst = round(($value->price * $quantityFirst), 2);

            // Obtener meses activos para este producto
            $activeMonths = isset($activeMonthsByProduct[$value->id])
                ? $activeMonthsByProduct[$value->id]->pluck('month_id')->all()
                : [];

            $months = [];
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($monthsRange as $month) {
                $isActive = in_array($month, $activeMonths);
                $amountMonth = $isActive ? $amountFirst : 0;
                $quantityMonth = $isActive ? $quantityFirst : 0;
                $totalAmount += $amountMonth;
                $totalQuantity += $quantityMonth;
                $months[] = number_format($amountMonth, 0, '', '.');
            }

            if ($bills == false) {
                $this->totalData1 += $totalAmount;
            }

            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->name ?? '',
                'totalQuantity' => number_format($totalQuantity, 2, ',', '.'),
                'totalAmount'   => number_format($totalAmount, 0, ',', '.'),
                'months'        => $months
            ];
        });

        return $result;
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
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        // Obtener todos los meses activos de una sola vez
        $activeMonths = DB::table('manpower_items')
            ->select('month_id')
            ->where('man_power_id', $manPowerId)
            ->whereIn('month_id', $monthsRange)
            ->distinct()
            ->pluck('month_id')
            ->toArray();

        $months = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($monthsRange as $month) {
            $isActive = in_array($month, $activeMonths);
            $amountMonth = $isActive ? $amount : 0;
            $quantityMonth = $isActive ? $quantity : 0;
            $totalAmount += $amountMonth;
            $totalQuantity += $quantityMonth;
            $months[] = number_format($amountMonth, 0, '', '.');
        }

        if ($bills == false) {
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
            ->get();

        // Precalcular superficies de los cost centers
        $surfaces = DB::table('cost_centers')
            ->whereIn('id', $costCentersId)
            ->pluck('surface', 'id');

        // Precalcular meses activos por producto y cost center
        $productIds = $products->pluck('id')->all();
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }
        $activeMonthsByProductCostCenter = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id')
            ->whereIn('man_power_id', $productIds)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy(function($item) {
                return $item->man_power_id . '-' . $item->cost_center_id;
            });

        $result = $products->map(function($value) use ($costCentersId, $surfaces, $activeMonthsByProductCostCenter, $monthsRange) {
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                $key = $value->id . '-' . $costCenter;
                $activeMonths = isset($activeMonthsByProductCostCenter[$key])
                    ? $activeMonthsByProductCostCenter[$key]->pluck('month_id')->all()
                    : [];

                foreach ($monthsRange as $month) {
                    $isActive = in_array($month, $activeMonths);
                    $amountMonth = $isActive ? $amountFirst : 0;
                    $quantityMonth = $isActive ? $quantityFirst : 0;
                    $totalAmount += $amountMonth;
                    $totalQuantity += $quantityMonth;
                }
            }
            $this->totalData2 += $totalAmount;
            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->name ?? '',
                'totalQuantity' => number_format($totalQuantity, 2, ',', '.'),
                'totalAmount'   => number_format($totalAmount, 0, ',', '.'),
            ];
        });

        return $result;
    }

    private function getResult2($value, $costCentersId)
    {
        // Precalcular superficies de los cost centers
        $surfaces = DB::table('cost_centers')
            ->whereIn('id', $costCentersId)
            ->pluck('surface', 'id');

        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        // Precalcular meses activos por cost center para este producto
        $activeMonthsByCostCenter = DB::table('manpower_items')
            ->select('cost_center_id', 'month_id')
            ->where('man_power_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy('cost_center_id');

        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $activeMonths = isset($activeMonthsByCostCenter[$costCenter])
                ? $activeMonthsByCostCenter[$costCenter]->pluck('month_id')->all()
                : [];

            foreach ($monthsRange as $month) {
                $isActive = in_array($month, $activeMonths);
                $amountMonth = $isActive ? $amountFirst : 0;
                $quantityMonth = $isActive ? $quantityFirst : 0;
                $totalAmount += $amountMonth;
                $totalQuantity += $quantityMonth;
            }
        }

        $this->totalData2 += $totalAmount;

        return [
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ];
    }


  
   

    

    private function buildData4($costCentersId, $season_id, $team_id)
    {
        // 1. Obtener TODOS los cost centers con su development_state y surface
        $allCostCenters = CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'surface', 'development_state_id', 'variety_id')
            ->get();

        $centerData = [];
        foreach ($allCostCenters as $cc) {
            $centerData[$cc->id] = [
                'surface' => (float) $cc->surface,
                'development_state_id' => $cc->development_state_id,
            ];
        }

        // Superficie total por development_state_id (TODOS los CCs)
        $surfaceByDevState = $allCostCenters->groupBy('development_state_id')
            ->map(fn($group) => $group->sum('surface'))
            ->toArray();

        $ccCountByDevState = $allCostCenters->groupBy('development_state_id')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 2. Obtener manpower_items indexados
        $months = [];
        $currentMonth = $this->month_id;
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $months[] = date('n', mktime(0, 0, 0, $x, 1));
        }

        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get();

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->man_power_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // 3. Obtener todos los man_powers con su subfamilia
        $products = ManPower::from('man_powers as mp')
            ->join('level3s as l3', 'mp.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->where('l2.name', 'mano de obra')
            ->select('mp.id', 'mp.price', 'mp.workday', 'mp.subfamily_id', 'l3.name as subfamily_name')
            ->whereIn('mp.id', $items->pluck('man_power_id')->unique())
            ->get();

        // 4. Calcular costo total por devState + subfamilia
        $costMatrix = [];

        foreach ($products as $product) {
            foreach ($itemIndex[$product->id] ?? [] as $costCenterId => $monthData) {
                $center = $centerData[$costCenterId] ?? null;
                if (!$center) continue;

                $surface = $center['surface'];
                $devStateId = $center['development_state_id'] ?? 0;

                $quantityFirst = round($product->workday * $surface, 2);
                $amountFirst = round($product->price * $quantityFirst, 2);
                $activeMonths = count($monthData);
                $totalAmount = $amountFirst * $activeMonths;

                $sfId = $product->subfamily_id;
                if (!isset($costMatrix[$devStateId][$sfId])) {
                    $costMatrix[$devStateId][$sfId] = 0;
                }
                $costMatrix[$devStateId][$sfId] += $totalAmount;
            }
        }

        // 5. Obtener nombres de estados de desarrollo y subfamilias
        $devStateIds = array_keys($costMatrix);
        foreach ($surfaceByDevState as $dsId => $surf) {
            if (!in_array($dsId, $devStateIds)) {
                $devStateIds[] = $dsId;
            }
        }
        $devStates = \App\Models\DevelopmentState::whereIn('id', $devStateIds)->pluck('name', 'id')->toArray();

        $allSubfamilyIds = [];
        foreach ($costMatrix as $devStateId => $subs) {
            foreach (array_keys($subs) as $sfId) {
                $allSubfamilyIds[$sfId] = true;
            }
        }
        $subfamilyNames = Level3::whereIn('id', array_keys($allSubfamilyIds))->pluck('name', 'id')->toArray();

        // 6. Construir respuesta
        $subfamilyList = [];
        foreach ($subfamilyNames as $id => $name) {
            $subfamilyList[] = ['id' => $id, 'name' => $name];
        }
        usort($subfamilyList, fn($a, $b) => strcmp($a['name'], $b['name']));

        $rows = [];
        foreach ($surfaceByDevState as $dsId => $totalSurface) {
            $subfamilyCosts = [];
            $totalCostPerHa = 0;
            foreach ($subfamilyList as $sf) {
                $totalCost = $costMatrix[$dsId][$sf['id']] ?? 0;
                $costPerHa = $totalSurface > 0 ? round($totalCost / $totalSurface) : 0;
                $subfamilyCosts[$sf['id']] = $costPerHa;
                $totalCostPerHa += $costPerHa;
            }
            $rows[] = [
                'development_state_id' => $dsId,
                'development_state_name' => $devStates[$dsId] ?? 'Sin Estado',
                'total_surface' => round($totalSurface, 2),
                'cost_centers_count' => $ccCountByDevState[$dsId] ?? 0,
                'subfamilyCosts' => $subfamilyCosts,
                'total_cost_per_ha' => $totalCostPerHa,
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['development_state_name'], $b['development_state_name']));

        // Totales globales
        $grandTotalSurface = array_sum($surfaceByDevState);
        $globalSubfamilyCosts = [];
        foreach ($subfamilyList as $sf) {
            $totalCost = 0;
            foreach ($costMatrix as $dsId => $subs) {
                $totalCost += $subs[$sf['id']] ?? 0;
            }
            $globalSubfamilyCosts[$sf['id']] = $grandTotalSurface > 0 ? round($totalCost / $grandTotalSurface) : 0;
        }
        $globalTotalCostPerHa = array_sum($globalSubfamilyCosts);

        return [
            'rows' => $rows,
            'subfamilyList' => $subfamilyList,
            'totalSurface' => round($grandTotalSurface, 2),
            'totalCCs' => count($costCentersId instanceof \Illuminate\Support\Collection ? $costCentersId->toArray() : $costCentersId),
            'globalSubfamilyCosts' => $globalSubfamilyCosts,
            'globalTotalCostPerHa' => $globalTotalCostPerHa,
        ];
    }
}