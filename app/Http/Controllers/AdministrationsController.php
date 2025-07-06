<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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


class AdministrationsController extends Controller
{
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

    public function __invoke()
    {
        // Calcular totales globales de cada rubro
        $user = Auth::user();
        $season_id = session('season_id');
        $team_id = $user->team_id;
        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
        $this->month_id = $season['month_id'];

        $this->totalAdministration = $this->getTotalAdministration($season_id, $team_id);
        $this->totalFertilizer = $this->getTotalFertilizer($season_id, $team_id);
        $this->totalManPower = $this->getTotalManPower($season_id, $team_id);
        $this->totalAgrochemical = $this->getTotalAgrochemical($season_id, $team_id);
        $this->totalSupplies = $this->getTotalSupplies($season_id, $team_id);
        $this->totalServices = $this->getTotalServices($season_id, $team_id);
        $this->totalField = $this->getTotalField($season_id, $team_id);

        // Sumar todos los rubros para el total absoluto
        $this->totalAbsolute = round($this->totalAdministration)
            + round($this->totalFertilizer)
            + round($this->totalManPower)
            + round($this->totalAgrochemical)
            + round($this->totalSupplies)
            + round($this->totalServices)
            + round($this->totalField);

        // Calcular el porcentaje de administración sobre el total absoluto
        $this->percentageAdministration = $this->totalAbsolute > 0
            ? round((round($this->totalAdministration) / $this->totalAbsolute) * 100, 2)
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

        $season = isset($season) ? $season : null;
        $percentageAdministration = $this->percentageAdministration;

        // Return igual que FieldsController pero para Administrations
        return Inertia::render('Administrations', compact(
            'units',
            'subfamilies',
            'months',
            'administrations',
            'data1',
            'data2',
            'season',
            'level2s',
            'team_id',
            'season_id',
            'percentageAdministration'
        ));
    }

    // Mover la función getTotalField aquí, fuera de __invoke
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
            $activeMonths = DB::table('field_items')
                ->where('field_id', $field->id)
                ->whereIn('month_id', $months)
                ->distinct('month_id')
                ->pluck('month_id');
            $countMonths = $activeMonths->count();
            if ($countMonths > 0) {
                $quantity = ($field->quantity !== null && ($field->quantity > 0)) ? ((in_array($field->unit_id ?? null, [2,4])) ? ($field->quantity / 1000) : $field->quantity) : 0;
                $amount = round($field->price * $quantity * $countMonths, 2);
                $total += $amount;
            }
        }
        return $total;
    }


    // Métodos de totales globales (idénticos a FieldsController, pero para cada rubro)
    private function getTotalAdministration($season_id, $team_id)
    {
        $total = 0;
        $administrations = \App\Models\Administration::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get();
        foreach ($administrations as $adm) {
            $quantity = (($adm->unit_id == 4) || ($adm->unit_id == 2)) ? ($adm->quantity / 1000) : $adm->quantity;
            $amount = round($adm->price * $quantity, 2);
            $months = DB::table('administration_items')
                ->where('administration_id', $adm->id)
                ->distinct('month_id')
                ->pluck('month_id');
            $total += $amount * count($months);
        }
        return $total;
    }

    private function getTotalFertilizer($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $fertilizers = \App\Models\Fertilizer::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($fertilizers as $fert) {
                $dose = (($fert->unit_id == 4 && $fert->unit_id_price == 3) || ($fert->unit_id == 2 && $fert->unit_id_price == 1)) ? ($fert->dose / 1000) : $fert->dose;
                $quantity = round($dose * $surface, 2);
                $amount = round($fert->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('fertilizer_items')
                        ->where('fertilizer_id', $fert->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    private function getTotalManPower($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $manpowers = \App\Models\ManPower::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($manpowers as $mp) {
                $quantity = round($mp->workday * $surface, 2);
                $amount = round($mp->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('manpower_items')
                        ->where('man_power_id', $mp->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    private function getTotalAgrochemical($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $agrochemicals = \App\Models\Agrochemical::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($agrochemicals as $agro) {
                $dose = (($agro->unit_id == 4 && $agro->unit_id_price == 3) || ($agro->unit_id == 2 && $agro->unit_id_price == 1)) ? ($agro->dose / 1000) : $agro->dose;
                if ($agro->dose_type_id == 1) {
                    $quantity = round($dose * $surface, 2);
                } elseif ($agro->dose_type_id == 2) {
                    $quantity = round((($agro->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantity = round($dose * $surface, 2);
                }
                $amount = round($agro->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('agrochemical_items')
                        ->where('agrochemical_id', $agro->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    private function getTotalSupplies($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $supplies = \App\Models\Supply::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($supplies as $supply) {
                $quantity = (($supply->unit_id == 4 && $supply->unit_id_price == 3) || ($supply->unit_id == 2 && $supply->unit_id_price == 1)) ? ($supply->quantity / 1000) : $supply->quantity;
                $quantity = round($quantity * $surface, 2);
                $amount = round($supply->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('supply_items')
                        ->where('supply_id', $supply->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
        }
        return $total;
    }

    private function getTotalServices($season_id, $team_id)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get();
        $total = 0;
        foreach ($costCenters as $costCenter) {
            $surface = $costCenter->surface ?? 1;
            $services = \App\Models\Service::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->get();
            foreach ($services as $service) {
                $quantity = (($service->unit_id == 4 && $service->unit_id_price == 3) || ($service->unit_id == 2 && $service->unit_id_price == 1)) ? ($service->quantity / 1000) : $service->quantity;
                $quantity = round($quantity * $surface, 2);
                $amount = round($service->price * $quantity, 2);
                foreach ($months as $month) {
                    $count = DB::table('service_items')
                        ->where('service_id', $service->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $costCenter->id)
                        ->count();
                    if ($count > 0) {
                        $total += $amount;
                    }
                }
            }
 
        }
        return $total;
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

    private function getTotal($id, $team_id)
    {   
        $total =Administration::from('administrations as f')
        ->join('administration_items as fi', 'fi.administration_id', 'f.id')
        ->join('level3s as s', 'f.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('f.team_id', $team_id)
        ->where('l2.id', $id)
        ->select('fi.administration_id')
        ->distinct('fi.administration_id')
        ->count();

        return $total;
    }


}






