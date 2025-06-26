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

     
   $data1 = Administration::from('administrations as f')
        ->join('level3s as s', 'f.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->join('level1s as l1', 'l2.level1_id', 'l1.id')
        ->select('l2.id', 'l2.name')
        ->where('f.team_id', $team_id)
        ->groupBy('l2.id', 'l2.name')
        ->get()
        ->transform(function($value) use ($team_id){
            return [
                'id'           => $value->id,
                'name'         => $value->name,
                'subfamilies'  => $this->getSubfamilies($value->id)
            ];
        });

        return Inertia::render('Administrations', compact('units', 'subfamilies', 'months', 'administrations', 'data1', 'season', 'level2s'));
    }




     public function getSubfamilies($id)
    {
        $subfamilies = Administration::from('administrations as f')
        ->join('level3s as s', 'f.subfamily_id', 's.id')
        ->join('level2s as l2', 's.level2_id', 'l2.id')
        ->where('l2.id', $id)
        ->select('s.id', 's.name')
        ->groupBy('s.id', 's.name')
        ->get()
        ->transform(function($subfamily){
            return [
                'id' => $subfamily->id,
                'name' => $subfamily->name,
                'products' => $this->getProducts($subfamily->id)
            ];
        });

        return $subfamilies;
    }


    public function getProducts($id)
    {
        $products = Administration::from('administrations as f')
        ->join('units as u', 'f.unit_id', 'u.id')
        ->select('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
        ->where('f.subfamily_id', $id)
        ->groupBy('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
        ->get()
        ->transform(function($value){

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

   
}

