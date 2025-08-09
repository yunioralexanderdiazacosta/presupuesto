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
use App\Http\Controllers\Traits\BudgetTotalsTrait;



class FertilizersController extends Controller
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
    public $totalHarvest = 0;
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

        $fertilizers = Fertilizer::with('subfamily:id,name', 'unit:id,name', 'items:id', 'unit2:id,name', 'user:id,name')->whereHas('items', function($query) use ($costCenters){
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
                'cc'            => array_values(array_unique($cc)),
                'user'          => $fertilizer->user ? ['name' => $fertilizer->user->name] : null
            ];
        });

        $products = $fertilizers->pluck('product_name')->unique()->values();

        $data = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('cost_centers as cc', 'fi.cost_center_id', 'cc.id')
            ->select('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
            ->whereIn('fi.cost_center_id', $costCenters->pluck('value'))
            ->groupBy('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
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

        $data3 = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('cost_centers as cc', 'fi.cost_center_id', 'cc.id')
            ->select('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
            ->whereIn('fi.cost_center_id', $costCenters->pluck('value'))
            ->groupBy('fi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
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


       
        // Obtener variedades asociadas a los cost centers de este equipo y temporada
        $varieties = \App\Models\Variety::whereIn('id',
            CostCenter::where('season_id', $season_id)
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

        // Calcular totales usando el trait
        $totalAdministration = $this->getTotalAdministration($season_id, $user->team_id);
        $totalField = $this->getTotalField($season_id, $user->team_id);
        $totalFertilizer = $this->getTotalFertilizer($season_id, $user->team_id);
        $totalAgrochemical = $this->getTotalAgrochemical($season_id, $user->team_id);
        $totalManPower = $this->getTotalManPower($season_id, $user->team_id);
        $totalSupplies = $this->getTotalSupplies($season_id, $user->team_id);
        $totalServices = $this->getTotalServices($season_id, $user->team_id);
        $totalHarvest = $this->getTotalHarvest($season_id, $user->team_id);

        $totalAbsolute = round($totalFertilizer) + round($totalAgrochemical) + round($totalManPower) + round($totalSupplies) + round($totalServices) + round($totalAdministration) + round($totalField) + round($totalHarvest);
        $percentage = $totalAbsolute > 0 ? round(((round($totalFertilizer) / $totalAbsolute) * 100), 2) : 0;

        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($totalFertilizer, 0, ',', '.');

        return Inertia::render('Fertilizers', compact('units', 'subfamilies', 'months', 'costCenters', 'groupings', 'fertilizers', 'season', 'data', 'data2', 'data3', 'totalData1', 'totalData2', 'percentage', 'varieties', 'fruits', 'products'));
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
            ->get();

        // Precalcular los meses activos para todos los productos de este cost center
        $productIds = $products->pluck('id')->all();
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }
        $activeMonthsByProduct = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'month_id')
            ->where('cost_center_id', $costCenterId)
            ->whereIn('fertilizer_id', $productIds)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy('fertilizer_id');

        $result = $products->map(function($value) use ($surface, $bills, $activeMonthsByProduct, $monthsRange) {
            $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') || ($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = $bills == true ? round($dose, 2) : round(($dose * $surface), 2);
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
                'unit'          => $value->name,
                'totalQuantity' => number_format($totalQuantity, 2, ',', '.'),
                'totalAmount'   => number_format($totalAmount, 0, ',', '.'),
                'months'        => $months
            ];
        });

        return $result;
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
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        // Obtener todos los meses activos de una sola vez
        $activeMonths = DB::table('fertilizer_items')
            ->select('month_id')
            ->where('fertilizer_id', $fertilizerId)
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
        $products = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
            ->whereIn('fi.cost_center_id', $costCentersId)
            ->where('f.subfamily_id', $subfamilyId)
            ->groupBy('f.id', 'f.product_name', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price', 'u.name')
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
        $activeMonthsByProductCostCenter = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id')
            ->whereIn('fertilizer_id', $productIds)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy(function($item) {
                return $item->fertilizer_id . '-' . $item->cost_center_id;
            });

        $result = $products->map(function($value) use ($costCentersId, $surfaces, $activeMonthsByProductCostCenter, $monthsRange) {
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') || ($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
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
        $activeMonthsByCostCenter = DB::table('fertilizer_items')
            ->select('cost_center_id', 'month_id')
            ->where('fertilizer_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $monthsRange)
            ->get()
            ->groupBy('cost_center_id');

        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $dose = (($value->unit_id == '4' && $value->unit_id_price == '3') || ($value->unit_id == '2' && $value->unit_id_price == '1')) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
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

}