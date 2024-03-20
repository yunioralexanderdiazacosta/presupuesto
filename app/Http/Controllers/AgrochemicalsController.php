<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Budget;
use App\Models\Subfamily;
use App\Models\CostCenter;
use App\Models\Unit;
use App\Models\Month;
use App\Models\Agrochemical;
use App\Models\DoseType;
use Inertia\Inertia;


class AgrochemicalsController extends Controller
{
    public $month_id = '';

    public function __invoke()
    {
        $user = Auth::user();

        $budget_id = session('budget_id');

        $budget = Budget::select('name', 'month_id')->where('id', $budget_id)->first();

        $this->month_id = $budget['month_id'];

        $subfamilies = Subfamily::where('id_form', 1)->get()->transform(function($subfamily){
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

        $months = Month::where('id', '>=', $budget->month_id)->get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
            ];
        });

        $doseTypes = DoseType::get()->transform(function($doseType){
            return [
                'label' => $doseType->name,
                'value' => $doseType->id
            ];
        });

        $costCenters = CostCenter::select('id', 'name')->where('budget_id', $budget_id)->whereHas('budget.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });

        $agrochemicals = Agrochemical::with('subfamily:id,name', 'unit:id,name', 'items:id')->whereHas('items', function($query) use ($costCenters){
            $query->whereIn('cost_center_id', $costCenters->pluck('value'));
        })->paginate(10)->through(function($agrochemical){
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
                'dose_type_id'  => $agrochemical->dose_type_id,
                'observations'  => $agrochemical->observations,
                'subfamily'     => $agrochemical->subfamily,
                'unit'          => $agrochemical->unit,
                'price'         => $agrochemical->price,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });

        $data = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('cost_centers as cc', 'ai.cost_center_id', 'cc.id')
        ->select('ai.cost_center_id', 'cc.name', 'cc.surface')
        ->whereIn('ai.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('ai.cost_center_id', 'cc.name', 'cc.surface')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface),
                'total' => $this->getTotal($value->cost_center_id)
            ];
        });

        return Inertia::render('Agrochemicals', compact('units', 'subfamilies', 'months', 'costCenters', 'agrochemicals', 'data', 'doseTypes', 'budget'));
    }

    private function getSubfamilies($costCenterId, $surface)
    {
        $subfamilies = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('subfamilies as s', 'a.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->where('ai.cost_center_id', $costCenterId)
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
        $products = Agrochemical::from('agrochemicals as a')
        ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
        ->join('units as u', 'a.unit_id', 'u.id')
        ->select('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.dose', 'a.mojamiento', 'u.name')
        ->where('ai.cost_center_id', $costCenterId)
        ->where('a.subfamily_id', $subfamilyId)
        ->groupBy('a.id', 'a.product_name', 'a.price', 'a.dose_type_id', 'a.dose', 'a.mojamiento', 'u.name')
        ->get()
        ->transform(function($value) use ($surface){
            if($value->dose_type_id == 1){
                $quantityFirst = round($value->dose * $surface, 2);
            }elseif($value->dose_type_id == 2){
                $quantityFirst = round((($value->mojamiento / 100) * $value->dose * $surface), 2);
            }
            $amountFirst = round($value->price * $quantityFirst, 2);
            $data = $this->getMonths($value->id, $quantityFirst, $amountFirst); 

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
        $total = DB::table('agrochemical_items')
        ->select('agrochemical_id')
        ->where('cost_center_id', $costCenterId)
        ->distinct('agrochemical_id')
        ->count();

        return $total;
    }

    private function getMonths($agrochemicalId, $quantity, $amount)
    {
        $data = Month::where('id', '>=', $this->month_id)->get();

        $months = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach($data as $month)
        {
            $count = DB::table('agrochemical_items')
            ->select('agrochemical_id')
            ->where('agrochemical_id', $agrochemicalId)
            ->where('month_id', $month->id)
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
}
