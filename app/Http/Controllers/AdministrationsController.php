<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Administration;
use Inertia\Inertia;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Season;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\Unit;
use App\Models\Month;
use App\Models\Service;

use App\Models\Level1;


use App\Http\Controllers\Traits\BudgetTotalsTrait;




class AdministrationsController extends Controller
{
    use BudgetTotalsTrait;
    public $totalAdministration = 0;
    public $totalFertilizer = 0;
    public $totalManPower = 0;
    public $totalAgrochemical = 0;
    public $totalSupplies = 0;
    public $totalServices = 0;
    public $totalField = 0;
    public $totalAbsolute = 0;
    public $percentageAdministration = 0;
    public $month_id = '';
    public $totalData2 = 0;

    
    /**
     * Suma la columna surface de todos los cost centers de la temporada indicada.
     * @param int $season_id
     * @return float|int
     */
    public function totalsurface($season_id)
    {
        return DB::table('cost_centers')->where('season_id', $season_id)->sum('surface');
    }

    public function __invoke()
    {
        // Log para verificar si el método __invoke se ejecuta
        Log::info('AdministrationsController __invoke ejecutado');
        // Calcular totales globales de cada rubro
        $user = Auth::user();
        $season_id = session('season_id');
        $team_id = $user->team_id;
        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
        $this->month_id = $season['month_id'];

        $totalAdministration = $this->getTotalAdministration($season_id, $team_id);
        $totalFertilizer = $this->getTotalFertilizer($season_id, $team_id);
        $totalManPower = $this->getTotalManPower($season_id, $team_id);
        $totalAgrochemical = $this->getTotalAgrochemical($season_id, $team_id);
        $totalSupplies = $this->getTotalSupplies($season_id, $team_id);
        $totalServices = $this->getTotalServices($season_id, $team_id);
        $totalField = $this->getTotalField($season_id, $team_id);

        // Sumar todos los rubros para el total absoluto
        $totalAbsolute = round($totalAdministration)
            + round($totalFertilizer)
            + round($totalManPower)
            + round($totalAgrochemical)
            + round($totalSupplies)
            + round($totalServices)
            + round($totalField);

        // Calcular el porcentaje de administración sobre el total absoluto
        $percentageAdministration = $totalAbsolute > 0
            ? round((round($totalAdministration) / $totalAbsolute) * 100, 2)
            : 0;


        // Obtener el Level1 correspondiente a 'administracion' para el equipo del usuario
        $level1 = Level1::where('name', 'Administracion')
            ->where('team_id', $user->team_id)
            ->first();
        $level2s =  Level2::from('level2s as l2')
            ->join('level1s as l1', 'l1.id', 'l2.level1_id')
            ->select('l2.id', 'l2.name')
            ->where('l1.team_id', $team_id)
            ->where('season_id', $season_id)
            ->where('l1.name', 'administracion')
            ->get()->transform(function($subfamily){
                return [
                    'label' => $subfamily->name, 
                    'value' => $subfamily->id
                ];
            });




        $subfamilies = collect();
        if ($level1) {
            $subfamilies = Level3::whereHas('level2', function($query) use ($level1) {
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

        // Nueva consulta para administrations, sin relación a cost centers
        $administrations = Administration::with(['subfamily:id,name', 'unit:id,name', 'items'])
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->paginate(10)
            ->through(function($admin) {
                return [
                    'id'            => $admin->id,
                    'product_name'  => $admin->product_name,
                    'quantity'      => $admin->quantity,
                    'subfamily_id'  => $admin->subfamily_id,
                    'unit_id'       => $admin->unit_id,
                    'price'         => $admin->price,
                    'observations'  => $admin->observations,
                    'subfamily'     => $admin->subfamily,
                    'unit'          => $admin->unit,
                    'months'        => $admin->items->pluck('month_id')->map(fn($m) => (string)$m)->unique()->values()->toArray(),
                ];
            });

        // --- Data1 y Data2 para tablas agrupadas y resumenes ---
        $data1 = Administration::from('administrations as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('l2.id', 'l2.name')
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('l2.id', 'l2.name')
            ->get()
            ->transform(function($value) use ($team_id, $season_id){
                return [
                    'id'           => $value->id,
                    'name'         => $value->name,
                    'subfamilies'  => $this->getSubfamilies($value->id, $team_id, $season_id)
                ];
            });

        $data2 = Level3::get()->map(function($subfamily) use ($team_id, $season_id) {
            $products = $this->getProducts2($subfamily->id, $team_id, $season_id);
            if ($products->count() > 0) {
                return [
                    'name' => $subfamily->name,
                    'products' => $products
                ];
            }
            return null;
        })->filter()->values();

        // --- Data3: Gastos por Hectarea plano: familia (level2), subfamilia (level3), producto, cantidad, monto total ---
        $surfaceTotal = $this->totalsurface($season_id);
        $data3 = \App\Models\Administration::from('administrations as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.price', 'f.quantity', 'f.unit_id', 'u.name as unit_name', 's.name as subfamily_name', 'l2.name as family_name')
            ->where('f.season_id', $season_id)
            ->get()
            ->map(function($value) use ($surfaceTotal, $season) {
                $quantity = (($value->unit_id == 4) || ($value->unit_id == 2)) ? ($value->quantity / 1000) : $value->quantity;
                $amount = $surfaceTotal > 0 ? ($value->price * $quantity) / $surfaceTotal : 0;
                // Calcular montos por mes
                $monthsArr = [];
                $currentMonth = $season ? $season->month_id : 1;
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    $count = DB::table('administration_items')
                        ->where('administration_id', $value->id)
                        ->where('month_id', $id)
                        ->count();
                    $amountMonth = $count > 0 ? $amount : 0;
                    $monthsArr[] = number_format($amountMonth, 0, '', '');
                }
                return [
                    'family'    => $value->family_name,
                    'subfamily' => $value->subfamily_name,
                    'product'   => $value->product_name,
                    'quantity'  => number_format($quantity, 0, '', ''),
                    'total'     => number_format($amount, 0, '', ''),
                    'months'    => $monthsArr
                ];
            });
       



      

        $season = isset($season) ? $season : null;
        // Usar la variable local $percentageAdministration para asegurar el valor correcto
        return Inertia::render('Administrations', compact(
            'units',
            'subfamilies',
            'months',
            'administrations',
            'data1',
            'data2',
            'data3',
            'season',
            'level2s',
            'team_id',
            'season_id',
            'percentageAdministration'
        ));
    }



    // --- Métodos auxiliares deben estar dentro de la clase, no fuera ---
    public function getSubfamilies($id, $team_id, $season_id)
    {
        $subfamilies = Administration::from('administrations as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->where('l2.id', $id)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->select('s.id', 's.name')
            ->groupBy('s.id', 's.name')
            ->get()
            ->transform(function($subfamily) use ($team_id, $season_id){
                return [
                    'id' => $subfamily->id,
                    'name' => $subfamily->name,
                    'products' => $this->getProducts($subfamily->id, $team_id, $season_id)
                ];
            });
        return $subfamilies;
    }

    public function getProducts($id, $team_id, $season_id)
    {
        $products = Administration::from('administrations as f')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
            ->where('f.subfamily_id', $id)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
            ->get()
            ->transform(function($value) use ($team_id, $season_id){
                $quantity = (($value->unit_id == 4) || ($value->unit_id == 2)) ? ($value->quantity / 1000) : $value->quantity;
                $amountFirst = round($value->price * $quantity, 2);
                $data = $this->getMonths($value->id, $quantity, $amountFirst);
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
    private function getMonths($administrationId, $quantity, $amount)
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
            $count = DB::table('administration_items')
            ->select('administration_id')
            ->where('administration_id', $administrationId)
            ->where('month_id', $month)
            ->count();

            $amountMonth = $count > 0 ? $amount : 0;
            $quantityMonth = $count > 0 ? $quantity : 0;
            $totalAmount += $amountMonth;
            $totalQuantity += $quantityMonth;
            array_push($months, number_format($amountMonth, 0, '', '.'));        
        }

        return [
            'months' => $months,
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ];
    }

    private function getMonthName($id)
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
        return $months[$id] ?? '';
    }

    private function getProducts2($subfamilyId, $team_id, $season_id)
    {
        $products = Administration::from('administrations as f')
            ->join('administration_items as fi', 'f.id', 'fi.administration_id')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
            ->where('f.subfamily_id', $subfamilyId)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
            ->get()
            ->transform(function($value) use ($team_id, $season_id){
                $data = $this->getResult2($value);
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

    private function getResult2($value)
    {
        $totalAmount = 0;
        $totalQuantity = 0;
        $currentMonth = $this->month_id;
        $amountFirst = round($value->price * $value->quantity, 2);

        $data = array();

        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            array_push($data, $id);
        }

        foreach($data as $month)
        {
            $count = DB::table('administration_items')
            ->select('administration_id')
            ->where('administration_id', $value->id)
            ->where('month_id', $month)
            ->count();

            $amountMonth = $count > 0 ? $amountFirst : 0;
            $quantityMonth = $count > 0 ? $value->quantity : 0;
            $totalAmount += $amountMonth;
            $totalQuantity += $quantityMonth;
        }

        $this->totalData2 += $totalAmount;

        return [
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ]; 
    }

    // Método getTotal eliminado porque no se utiliza en el controlador


}






