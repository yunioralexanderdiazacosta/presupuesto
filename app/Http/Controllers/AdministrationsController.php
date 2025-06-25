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
        public $month_id = '';

     public function __invoke()
    {
          $user = Auth::user();

        $season_id = session('season_id');

           $team_id = $user->team_id;

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];


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
    
        // Nueva consulta para data, sin relación a cost centers
        $data = Administration::select('id', 'product_name', 'quantity', 'subfamily_id', 'unit_id', 'price', 'observations')
            ->with(['subfamily:id,name', 'unit:id,name', 'items'])
            ->get()
            ->map(function($admin) {
                $admin->months = $admin->items->pluck('month_id')->toArray();
                return $admin;
            });

      

        return Inertia::render('Administrations', compact('units', 'subfamilies', 'months', 'administrations', 'data', 'season', 'level2s'));
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

   
}

