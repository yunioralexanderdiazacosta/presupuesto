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
use App\Models\Fertilizer;
use Inertia\Inertia;


class FertilizersController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $budget_id = session('budget_id');

        $budget = Budget::select('name')->where('id', $budget_id)->first();

        $subfamilies = Subfamily::where('id_form', 2)->get()->transform(function($subfamily){
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

        $months = Month::get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
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

        $fertilizers = Fertilizer::with('subfamily:id,name', 'unit:id,name', 'items:id')->whereHas('items', function($query) use ($costCenters){
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
                'observations'  => $fertilizer->observations,
                'subfamily'     => $fertilizer->subfamily,
                'unit'          => $fertilizer->unit,
                'price'         => $fertilizer->price,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });


        $data = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('cost_centers as cc', 'fi.cost_center_id', 'cc.id')
        ->select('fi.cost_center_id', 'cc.name', 'cc.surface')
        ->whereIn('fi.cost_center_id', $costCenters->pluck('value'))
        ->groupBy('fi.cost_center_id', 'cc.name', 'cc.surface')
        ->get()
        ->transform(function($value) use ($costCenters){
            return [
                'id' => $value->cost_center_id,
                'name' => $value->name,
                'subfamilies' => $this->getSubfamilies($value->cost_center_id, $value->surface)
            ];
        });

        return Inertia::render('Fertilizers', compact('units', 'subfamilies', 'months', 'costCenters', 'fertilizers', 'budget', 'data'));
    }

    private function getSubfamilies($costCenterId, $surface)
    {
        $subfamilies = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('subfamilies as s', 'f.subfamily_id', 's.id')
        ->select('s.id', 's.name')
        ->where('fi.cost_center_id', $costCenterId)
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
        $products = Fertilizer::from('fertilizers as f')
        ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
        ->join('units as u', 'f.unit_id', 'u.id')
        ->select('f.id', 'f.product_name', 'f.price', 'f.dose', 'u.name')
        ->where('fi.cost_center_id', $costCenterId)
        ->where('f.subfamily_id', $subfamilyId)
        ->groupBy('f.id', 'f.product_name', 'f.price', 'f.dose', 'u.name')
        ->get()
        ->transform(function($value) use ($surface){
            $quantityFirst = round(($value->dose * $surface), 2);
            $amountFirst = round(($value->price * $quantityFirst), 2);
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

    private function getMonths($fertilizerId, $quantity, $amount)
    {
        $data = Month::get();

        $months = [];
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach($data as $month)
        {
            $count = DB::table('fertilizer_items')
            ->select('fertilizer_id')
            ->where('fertilizer_id', $fertilizerId)
            ->where('month_id', $month->id)
            ->count();

            $amountMonth = $count > 0 ? $amount : 0;
            $quantityMonth = $count > 0 ? $quantity : 0;
            $totalAmount += $amountMonth;
            $totalQuantity += $quantityMonth;
            array_push($months, $amountMonth);        
        }

        return [
            'months' => $months,
            'totalAmount' => round($totalAmount, 2),
            'totalQuantity' => round($totalQuantity, 2)
        ];
    }
}