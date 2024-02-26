<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $subfamilies = Subfamily::get()->transform(function($subfamily){
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

        return Inertia::render('Fertilizers', compact('units', 'subfamilies', 'months', 'costCenters', 'fertilizers', 'budget'));
    }
}
