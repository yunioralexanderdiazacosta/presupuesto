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
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Supply;
use App\Models\Service;
use App\Models\DoseType;
use App\Models\Product2;
use Inertia\Inertia;

use App\Http\Controllers\Traits\BudgetTotalsTrait;
use Illuminate\Support\Facades\Log;


class AgrochemicalsController extends Controller
{
    use BudgetTotalsTrait;


    public $month_id = '';

    public $totalData1 = 0;

    public $totalData2 = 0;


    public $totalFertilizer = 0;
    public $totalManPower = 0;
    public $totalSupplies = 0;
    public $totalServices = 0;
    public $totalAgrochemical = 0;
      public $totalHarvest = 0;
    public $totalAdministration = 0;
    public $totalField = 0;
    public $totalAbsolute = 0;
    public $percentageAgrochemical = 0;

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
        ->where('l2.name', 'agroquimicos')
        ->where('season_id', $season_id)
        ->get()->transform(function($subfamily){
            return [
                'label' => $subfamily->name, 
                'value' => $subfamily->id
            ];
        });

        /*
        $subfamilies = Level3::whereHas('level2.level1', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->whereHas('level2', function($query){
            $query->where('name', 'agroquimicos');
        })->get()->transform(function($subfamily){
            return [
                'label' => $subfamily->name, 
                'value' => $subfamily->id
            ];
        });
        */

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

        $doseTypes = DoseType::get()->transform(function($doseType){
            return [
                'label' => $doseType->name,
                'value' => $doseType->id
            ];
        });

        $costCenters = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });

        $agrochemicals = Agrochemical::with('subfamily:id,name', 'unit:id,name', 'items:id', 'dosetype:id,name')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->paginate(1000)->through(function($agrochemical){
            $items = $agrochemical->items->pluck('pivot');
            $months = array_column($items->toArray(), 'month_id');
            $cc = array_column($items->toArray(), 'cost_center_id');
            return [
                'id'            => $agrochemical->id,
                'product_name'  => $agrochemical->product_name,
                'dose'          => $agrochemical->dose,
                'price'         => $agrochemical->price,
                'mojamiento'    => $agrochemical->mojamiento,
                'subfamily_id'  => $agrochemical->subfamily_id,
                'unit_id'       => $agrochemical->unit_id,
                'unit_id_price' => $agrochemical->unit_id_price,
                'dose_type_id'  => $agrochemical->dose_type_id,
                'observations'  => $agrochemical->observations,
                'subfamily'     => $agrochemical->subfamily,
                'unit'          => $agrochemical->unit,
                'price'         => $agrochemical->price,
                'dosetype'      => $agrochemical->dosetype,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });

        $products = Product2::select('name', 'level3', 'price', 'unit_price_id')->get()->toArray();


        $data = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('cost_centers as cc', 'ai.cost_center_id', 'cc.id')
        ->select('ai.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // <-- Agregamos variety_id
        ->whereIn('ai.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('ai.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // <-- Agregamos variety_id
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id, // <-- Agregamos variety_id al array
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface)
            ];
        });

        $data3 = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('cost_centers as cc', 'ai.cost_center_id', 'cc.id')
        ->select('ai.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // <-- Agregamos variety_id
        ->whereIn('ai.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('ai.cost_center_id', 'cc.name', 'cc.surface', 'cc.variety_id') // <-- Agregamos variety_id
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'variety_id' => $value->variety_id, // <-- Agregamos variety_id al array
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, null, true)
            ];
        }); 

        $costCentersId = $costCenters->pluck('value');

        $data2 = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('level3s as s', 'a.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('l2.name', 'agroquimicos')
        ->select('s.id', 's.name')
        ->whereIn('ai.cost_center_id', $costCentersId)
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
        $this->percentageAgrochemical = $this->totalAbsolute > 0
            ? round((round($this->totalAgrochemical) / $this->totalAbsolute) * 100, 2)
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
        $percentageAgrochemical = $this->percentageAgrochemical;

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

        return Inertia::render('Agrochemicals', compact(
            'units', 'subfamilies', 'months', 'costCenters', 'agrochemicals', 'data', 'data2', 'data3', 'doseTypes', 'season',
            'totalData1', 'totalData2',
            'totalAgrochemical', 'totalFertilizer', 'totalManPower', 'totalSupplies', 'totalServices', 'totalAdministration', 'totalField', 'totalHarvest', 'totalAbsolute',
            'percentageAgrochemical',
            'varieties', 'fruits','products'
        ));
    }

    private function getSubfamilies($costCenterId, $surface = null, $bills = false)
    {
        $subfamilies = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('level3s as s', 'a.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('l2.name', 'agroquimicos')
        ->select('s.id', 's.name')
        ->where('ai.cost_center_id', $costCenterId)
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
        $products = Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.unit_id', 'a.unit_id_price', 'a.dose', 'a.mojamiento', 'u.name')
            ->where('ai.cost_center_id', $costCenterId)
            ->where('a.subfamily_id', $subfamilyId)
            ->groupBy('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.unit_id', 'a.unit_id_price', 'a.dose', 'a.mojamiento', 'u.name')
            ->get();

        // Obtener todos los meses con datos para todos los productos de este cost center en una sola consulta
        $productIds = $products->pluck('id');
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }
        $monthsWithData = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('agrochemical_id', $productIds)
            ->where('cost_center_id', $costCenterId)
            ->whereIn('month_id', $monthsRange)
            ->groupBy('agrochemical_id', 'month_id')
            ->get()
            ->groupBy('agrochemical_id'); // [product_id => [rows...]]

        $result = $products->map(function($value) use ($surface, $bills, $monthsRange, $monthsWithData) {
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            if ($value->dose_type_id == 1) {
                $quantityFirst = $bills == true ? round($dose, 2) : round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = $bills == true ? round((($value->mojamiento / 100) * $dose), 2) : round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);

            $monthsData = collect($monthsWithData[$value->id] ?? [])->keyBy('month_id');
            $months = [];
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($monthsRange as $month) {
                $count = isset($monthsData[$month]) ? $monthsData[$month]->count : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $quantityMonth = $count > 0 ? $quantityFirst : 0;
                $totalAmount += $amountMonth;
                $totalQuantity += $quantityMonth;
                $months[] = number_format($amountMonth, 0, '', '.');
            }
            // Sumar al total global si corresponde
            // (No se puede acceder a $this->totalData1 aquí, pero se puede sumar después si es necesario)
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



    private function getMonths($agrochemicalId, $quantity, $amount, $bills)
    {
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }

        // Traer todos los meses con datos para este agroquímico en una sola consulta
        $monthsWithData = DB::table('agrochemical_items')
            ->select('month_id', DB::raw('COUNT(*) as count'))
            ->where('agrochemical_id', $agrochemicalId)
            ->whereIn('month_id', $monthsRange)
            ->groupBy('month_id')
            ->pluck('count', 'month_id'); // [month_id => count]

        $months = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($monthsRange as $month) {
            $count = $monthsWithData[$month] ?? 0;
            $amountMonth = $count > 0 ? $amount : 0;
            $quantityMonth = $count > 0 ? $quantity : 0;
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
        $products = Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento', 'u.name')
            ->whereIn('ai.cost_center_id', $costCentersId)
            ->where('a.subfamily_id', $subfamilyId)
            ->groupBy('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento', 'u.name')
            ->get();

        // Obtener superficies de todos los cost centers en una sola consulta
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Precalcular los totales de todos los productos y cost centers
        $result = $products->map(function($value) use ($costCentersId, $surfaces) {
            $totalAmount = 0;
            $totalQuantity = 0;
            foreach ($costCentersId as $costCenter) {
                $surface = $surfaces[$costCenter] ?? 1;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);

                // Traer todos los meses con datos para este producto y cost center en una sola consulta
                $currentMonth = $this->month_id;
                $monthsRange = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    $monthsRange[] = $id;
                }
                $monthsWithData = DB::table('agrochemical_items')
                    ->select('month_id', DB::raw('COUNT(*) as count'))
                    ->where('agrochemical_id', $value->id)
                    ->where('cost_center_id', $costCenter)
                    ->whereIn('month_id', $monthsRange)
                    ->groupBy('month_id')
                    ->pluck('count', 'month_id');

                foreach ($monthsRange as $month) {
                    $count = $monthsWithData[$month] ?? 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $quantityMonth = $count > 0 ? $quantityFirst : 0;
                    $totalAmount += $amountMonth;
                    $totalQuantity += $quantityMonth;
                }
            }
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
        $totalAmount = 0;
        $totalQuantity = 0;
        $currentMonth = $this->month_id;
        $monthsRange = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $monthsRange[] = $id;
        }
        // Obtener superficies de todos los cost centers en una sola consulta
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Traer todos los conteos de meses para todos los cost centers en una sola consulta
        $monthsWithData = DB::table('agrochemical_items')
            ->select('cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('agrochemical_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $monthsRange)
            ->groupBy('cost_center_id', 'month_id')
            ->get()
            ->groupBy('cost_center_id'); // [cost_center_id => [rows...]]

        foreach ($costCentersId as $costCenter) {
            $surface = $surfaces[$costCenter] ?? 1;
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            if ($value->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);

            $monthsData = collect($monthsWithData[$costCenter] ?? [])->keyBy('month_id');
            foreach ($monthsRange as $month) {
                $count = isset($monthsData[$month]) ? $monthsData[$month]->count : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $quantityMonth = $count > 0 ? $quantityFirst : 0;
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