<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subfamily;
use App\Models\CostCenter;
use App\Models\Unit;
use App\Models\Month;
use App\Models\Agrochemical;
use Inertia\Inertia;


class AgrochemicalsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

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

        $costCenters = CostCenter::select('id', 'name')->whereHas('budget.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });

        $agrochemicals = Agrochemical::with('subfamily:id,name', 'unit:id,name', 'items:id')->paginate(10)->through(function($agrochemical){
            $items = $agrochemical->items->pluck('pivot');
            $months = array_column($items->toArray(), 'month_id');
            $cc = array_column($items->toArray(), 'cost_center_id');
            return [
                'id'            => $agrochemical->id,
                'product_name'  => $agrochemical->product_name,
                'dose_type'     => $agrochemical->dose_type,
                'dose'          => $agrochemical->dose,
                'price'         => $agrochemical->price,
                'mojamiento'    => $agrochemical->mojamiento,
                'subfamily_id'  => $agrochemical->subfamily_id,
                'unit_id'       => $agrochemical->unit_id,
                'observations'  => $agrochemical->observations,
                'subfamily'     => $agrochemical->subfamily,
                'unit'          => $agrochemical->unit,
                'price'         => $agrochemical->price,
                'months'        => array_unique($months),
                'cc'            => array_values(array_unique($cc))
            ];
        });

        return Inertia::render('Agrochemicals', compact('units', 'subfamilies', 'months', 'costCenters', 'agrochemicals'));
    }
}
