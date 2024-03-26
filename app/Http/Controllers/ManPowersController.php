<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Budget;
use App\Models\ManPower;
use App\Models\Subfamily;
use App\Models\CostCenter;
use App\Models\Month;
use Inertia\Inertia;

class ManPowersController extends Controller
{
    public $month_id = '';

    public function __invoke()
    {
        $user = Auth::user();

        $budget_id = session('budget_id');

        $budget = Budget::select('name', 'month_id')->where('id', $budget_id)->first();

        $this->month_id = $budget['month_id'];

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


        $costCenters = CostCenter::select('id', 'name')->where('budget_id', $budget_id)->whereHas('budget.team', function($query) use ($user){
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

        return Inertia::render('ManPowers', compact('subfamilies', 'months', 'costCenters', 'manPowers', 'budget', 'data', 'data2'));
    }

    private function getSubfamilies($costCenterId, $surface)
    {
        $subfamilies = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->join('subfamilies as s', 'mp.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->where('mpi.cost_center_id', $costCenterId)
        ->groupBy('s.id', 's.name')
        ->get()
        ->transform(function($subfamily) use ($costCenterId, $surface){
            return [
                'id' => $subfamily->id,
                'name' => $subfamily->name,
                'products' => $this->getProducts($subfamily->id, $costCenterId, $surface)
            ];
        });

        return $subfamilies;
    }

    private function getProducts($subfamilyId, $costCenterId, $surface)
    {
        $products = ManPower::from('man_powers as mp')
        ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
        ->select('mp.id', 'mp.product_name', 'mp.price', 'mp.workday')
        ->where('mpi.cost_center_id', $costCenterId)
        ->where('mp.subfamily_id', $subfamilyId)
        ->groupBy('mp.id', 'mp.product_name', 'mp.price', 'mp.workday')
        ->get()
        ->transform(function($value) use ($surface){
            $quantityFirst = round(($value->workday * $surface), 2);
            $amountFirst = round(($value->price * $quantityFirst), 2);
            $data = $this->getMonths($value->id, $quantityFirst, $amountFirst);

            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
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

    private function getMonths($manPowerId, $quantity, $amount)
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
        ->select('mp.id', 'mp.product_name', 'mp.price', 'mp.workday')
        ->whereIn('mpi.cost_center_id', $costCentersId)
        ->where('mp.subfamily_id', $subfamilyId)
        ->groupBy('mp.id', 'mp.product_name', 'mp.price', 'mp.workday')
        ->get()
        ->transform(function($value) use ($costCentersId){
            $data = $this->getResult2($value, $costCentersId);
            return [
                'id'            => $value->id,
                'name'          => $value->product_name,
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

        return [
            'totalAmount' => number_format($totalAmount, 0, ',', '.'),
            'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
        ];
    }
}