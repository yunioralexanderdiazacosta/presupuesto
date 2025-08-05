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
use App\Models\Service;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Agrochemical;
use App\Models\Supply;
use App\Models\DoseType;
use Inertia\Inertia;
use App\Models\Harvest;
use App\Http\Controllers\Traits\BudgetTotalsTrait;


class HarvestsController extends Controller
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
    public $totalServices = 0;
    public $totalAgrochemical = 0;
    public $totalManPower = 0;
    public $totalSupplies = 0;

    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];


        // Obtener el Level1 correspondiente a 'Cosecha' para el equipo del usuario
        $level1 = \App\Models\Level1::where('name', 'Cosecha')
            ->where('team_id', $user->team_id)
            ->first();

        $level2s = collect();
        if ($level1) {
            $level2s = \App\Models\Level2::from('level2s as l2')
                ->join('level1s as l1', 'l1.id', 'l2.level1_id')
                ->select('l2.id', 'l2.name')
                ->where('l1.team_id', $user->team_id)
                ->where('season_id', $season_id)
                ->where('l1.name', 'Cosecha')
                ->get()->transform(function($subfamily){
                    return [
                        'label' => $subfamily->name,
                        'value' => $subfamily->id
                    ];
                });
        }

        $subfamilies = collect();
        if ($level1) {
            $subfamilies = \App\Models\Level3::whereHas('level2', function($query) use ($level1) {
                $query->where('level1_id', $level1->id);
            })
            ->get()
            ->transform(function($subfamily){
                return [
                    'label' => $subfamily->name,
                    'value' => $subfamily->id
                ];
            });
        }

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

        $harvests = Harvest::with('subfamily:id,name', 'unit:id,name', 'unit2:id,name', 'items:id')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->paginate(10)->through(function($harvest){
            $items = $harvest->items->pluck('pivot');
            $months = array_column($items->toArray(), 'month_id');
            $cc = array_column($items->toArray(), 'cost_center_id');
            return [
                'id'            => $harvest->id,
                'product_name'  => $harvest->product_name,
                'quantity'      => $harvest->quantity,
                'subfamily_id'  => $harvest->subfamily_id,
                'unit_id'       => $harvest->unit_id,
                'unit_id_price' => $harvest->unit_id_price,
                'observations'  => $harvest->observations,
                'subfamily'     => $harvest->subfamily,
                'unit'          => $harvest->unit,
                'unit2'         => $harvest->unit2,
                'price'         => $harvest->price,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });


        $data = Harvest::from('harvests as h')
        ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
        ->join('cost_centers as cc', 'hi.cost_center_id', 'cc.id')
        ->select('hi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
        ->whereIn('hi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('hi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
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

        $data3 = Harvest::from('harvests as h')
        ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
        ->join('cost_centers as cc', 'hi.cost_center_id', 'cc.id')
        ->select('hi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
        ->whereIn('hi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('hi.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id')
        ->get();

    // Preload subfamilies and totals for all cost centers in one go
    $costCenterIds = $data3->pluck('cost_center_id');

    // Preload subfamilies for all cost centers
    $subfamiliesRaw = Harvest::from('harvests as h')
        ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
        ->join('level3s as l3', 'h.subfamily_id', 'l3.id')
        ->join('level2s as l2', 'l3.level2_id', 'l2.id')
         ->join('level1s as l1', 'l2.level1_id', 'l1.id')
        ->where('l1.name', 'Cosecha')
        ->whereIn('hi.cost_center_id', $costCenterIds)
        ->select('hi.cost_center_id', 'l3.id as subfamily_id', 'l3.name as subfamily_name')
        ->groupBy('hi.cost_center_id', 'l3.id', 'l3.name')
        ->get();

    // Preload products for all subfamilies in all cost centers
    $productsRaw = Harvest::from('harvests as h')
        ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
        ->leftJoin('units as u', 'h.unit_id_price', 'u.id')
        ->select('h.id', 'h.product_name', 'h.price', 'h.quantity', 'h.unit_id', 'h.unit_id_price', 'u.name as unit_name', 'h.subfamily_id', 'hi.cost_center_id', 'cc.surface')
        ->join('cost_centers as cc', 'hi.cost_center_id', 'cc.id')
        ->whereIn('hi.cost_center_id', $costCenterIds)
        ->get();

    // Group products by cost_center_id and subfamily_id
    $productsByCostCenterAndSubfamily = $productsRaw->groupBy(function($item) {
        return $item->cost_center_id . '-' . $item->subfamily_id;
    });

    // Group subfamilies by cost_center_id
    $subfamiliesByCostCenter = $subfamiliesRaw->groupBy('cost_center_id');

    // Preload totals for all cost centers
    $totalsByCostCenter = DB::table('harvest_items')
        ->select('cost_center_id', DB::raw('COUNT(DISTINCT harvest_id) as total'))
        ->whereIn('cost_center_id', $costCenterIds)
        ->groupBy('cost_center_id')
        ->pluck('total', 'cost_center_id');

    $month_id = $this->month_id;
    $monthsRange = [];
    for ($x = $month_id; $x < $month_id + 12; $x++) {
        $id = date('n', mktime(0, 0, 0, $x, 1));
        $monthsRange[] = $id;
    }

    // Preload all harvest_items for all products in one query
    $harvestItemData = DB::table('harvest_items')
        ->select('harvest_id', 'cost_center_id', 'month_id')
        ->whereIn('cost_center_id', $costCenterIds)
        ->whereIn('month_id', $monthsRange)
        ->get()
        ->groupBy(function($item) {
            return $item->cost_center_id . '-' . $item->harvest_id;
        });

    $data3 = $data3->map(function($value) use ($subfamiliesByCostCenter, $productsByCostCenterAndSubfamily, $totalsByCostCenter, $monthsRange, $harvestItemData) {
        $costCenterId = $value->cost_center_id;
        $surface = $value->surface;
        $subfamilies = $subfamiliesByCostCenter->get($costCenterId, collect())->map(function($subfamily) use ($costCenterId, $productsByCostCenterAndSubfamily, $monthsRange, $harvestItemData, $surface) {
            $key = $costCenterId . '-' . $subfamily->subfamily_id;
            $products = $productsByCostCenterAndSubfamily->get($key, collect())->map(function($product) use ($costCenterId, $monthsRange, $harvestItemData, $surface) {
                // Calcular cantidad y monto como en getProducts
                $quantity = (($product->unit_id == 4 && $product->unit_id_price == 3) || ($product->unit_id == 2 && $product->unit_id_price == 1)) ? ($product->quantity / 1000) : $product->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($product->price * $quantityFirst, 2);
                // Obtener meses activos
                $harvestKey = $costCenterId . '-' . $product->id;
                $activeMonths = $harvestItemData->get($harvestKey, collect())->pluck('month_id')->toArray();
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
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'unit' => $product->unit_name ?? '',
                    'totalQuantity' => number_format($totalQuantity, 2, ',', '.'),
                    'totalAmount' => number_format($totalAmount, 0, ',', '.'),
                    'months' => $months
                ];
            });
            return [
                'id' => $subfamily->subfamily_id,
                'name' => $subfamily->subfamily_name,
                'products' => $products
            ];
        });
        return [
            'id' => $costCenterId,
            'name' => $value->name,
            'variety_id' => $value->variety_id,
            'subfamilies' => $subfamilies,
            'total' => $totalsByCostCenter[$costCenterId] ?? 0
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

        $data2 = Harvest::from('harvests as s')
        ->join('harvest_items as si', 's.id', 'si.harvest_id')
        ->join('level3s as l3', 's.subfamily_id', 'l3.id')
        ->join('level2s as l2', 'l3.level2_id', 'l2.id')
         ->join('level1s as l1', 'l2.level1_id', 'l1.id')
        ->where('l1.name', 'Cosecha')
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

       

      // Calcular totales usando el trait
        $totalSupplies = $this->getTotalSupplies($season_id, $user->team_id);
        $totalFertilizer = $this->getTotalFertilizer($season_id, $user->team_id);
        $totalManPower = $this->getTotalManPower($season_id, $user->team_id);
        $totalAgrochemical = $this->getTotalAgrochemical($season_id, $user->team_id);
        $totalServices = $this->getTotalServices($season_id, $user->team_id);
        $totalAdministration = $this->getTotalAdministration($season_id, $user->team_id);
        $totalField = $this->getTotalField($season_id, $user->team_id);
        $totalHarvests = $this->getTotalHarvest($season_id, $user->team_id);

        $totalAbsolute = $totalSupplies + $totalFertilizer + $totalManPower + $totalAgrochemical + $totalServices + $totalAdministration + $totalField + $totalHarvests;
        $percentage = $totalAbsolute > 0 ? round(($totalHarvests / $totalAbsolute) * 100, 2) : 0;

        $totalData1 = number_format($this->totalData1, 0, ',', '.');
        $totalData2 = number_format($this->totalData2, 0, ',', '.');

        return Inertia::render('Harvests', compact('units', 'subfamilies', 'months', 'costCenters', 'groupings', 'harvests', 'data', 'data2', 'data3', 'season', 'totalData1', 'totalData2', 'percentage', 'varieties', 'fruits', 'level2s'));
    }

    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = Harvest::from('harvests as s')
        ->join('harvest_items as si', 's.id', 'si.harvest_id')
        ->join('level3s as l3', 's.subfamily_id', 'l3.id')
        ->join('level2s as l2', 'l3.level2_id', 'l2.id')
        ->join('level1s as l1', 'l2.level1_id', 'l1.id')
        ->where('l1.name', 'Cosecha')
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
        $products = Harvest::from('harvests as s')
        ->join('harvest_items as si', 's.id', 'si.harvest_id')
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
        $total = DB::table('harvest_items')
        ->select('harvest_id')
        ->where('cost_center_id', $costCenterId)
        ->distinct('harvest_id')
        ->count();

        return $total;
    }

    private function getMonths($harvestId, $quantity, $amount, $bills)
    {
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        // Obtener todos los meses activos de una sola vez
        $activeMonths = DB::table('harvest_items')
            ->select('month_id')
            ->where('harvest_id', $harvestId)
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
        // Preload surfaces for all cost centers
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Preload all harvest_items for these products and cost centers
        $productsRaw = Harvest::from('harvests as s')
            ->join('harvest_items as si', 's.id', 'si.harvest_id')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name as unit_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->where('s.subfamily_id', $subfamilyId)
            ->get();

        // Agrupar por producto
        $productsGrouped = $productsRaw->groupBy('id');

        // Preload all harvest_items for these products and cost centers
        $harvestItems = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id')
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('harvest_id', $productsRaw->pluck('id')->unique())
            ->get();

        $month_id = $this->month_id;
        $monthsRange = [];
        for ($x = $month_id; $x < $month_id + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        $products = $productsGrouped->map(function($group) use ($surfaces, $harvestItems, $costCentersId, $monthsRange) {
            $value = $group->first();
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($costCentersId as $costCenter) {
                $surface = $surfaces[$costCenter] ?? 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                $items = $harvestItems->where('harvest_id', $value->id)->where('cost_center_id', $costCenter);
                $activeMonths = $items->pluck('month_id')->toArray();
                foreach ($monthsRange as $month) {
                    $isActive = in_array($month, $activeMonths);
                    $amountMonth = $isActive ? $amountFirst : 0;
                    $quantityMonth = $isActive ? $quantityFirst : 0;
                    $totalAmount += $amountMonth;
                    $totalQuantity += $quantityMonth;
                }
            }
            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
                'unit'          => $value->unit_name ?? '',
                'totalQuantity' => number_format($totalQuantity, 2, ',', '.'),
                'totalAmount'   => number_format($totalAmount, 0, ',', '.'),
            ];
        })->values();

        return $products;
    }

    private function getResult2($value, $costCentersId)
    {
        // Preload surfaces for all cost centers
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Preload all harvest_items for this product and cost centers
        $harvestItems = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id')
            ->whereIn('cost_center_id', $costCentersId)
            ->where('harvest_id', $value->id)
            ->get();

        $month_id = $this->month_id;
        $monthsRange = [];
        for ($x = $month_id; $x < $month_id + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        $totalAmount = 0;
        $totalQuantity = 0;
        foreach($costCentersId as $costCenter){
            $surface = $surfaces[$costCenter] ?? 0;
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $items = $harvestItems->where('cost_center_id', $costCenter);
            $activeMonths = $items->pluck('month_id')->toArray();
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