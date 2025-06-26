<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Field;
use App\Models\Season;
use App\Models\Level1;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\Unit;
use App\Models\CostCenter;
use Inertia\Inertia;

use Illuminate\Http\Request;

class FieldsController extends Controller
{
    public $month_id = '';

    //public $totalData2 = 0;

    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $team_id = $user->team_id;

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];



         // Obtener el Level1 correspondiente a 'field' para el equipo del usuario
        $level1 = Level1::where('name', 'Generales campo')
            ->where('team_id', $user->team_id)
            ->first();


        $level2s =  Level2::from('level2s as l2')
        ->join('level1s as l1', 'l1.id', 'l2.level1_id')
        ->select('l2.id', 'l2.name')
        ->where('l1.team_id', $team_id)
        ->where('season_id', $season_id)
        ->where('l1.name', 'Generales campo')
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




        $fields = Field::with(['subfamily:id,name', 'unit:id,name', 'items'])
        ->paginate(10)
        ->through(function($field) {
        return [
                'id'            => $field->id,
                'product_name'  => $field->product_name,
                'quantity'      => $field->quantity,
                'subfamily_id'  => $field->subfamily_id,
                'unit_id'       => $field->unit_id,
                'price'         => $field->price,
                'observations'  => $field->observations,
                'subfamily'     => $field->subfamily,
                'unit'          => $field->unit,
                'months'        => $field->items->pluck('month_id')->map(fn($m) => (string)$m)->unique()->values()->toArray(),
            ];
        });


        // Nueva consulta para data, sin relación a cost centers
        $data = Field::select('id', 'product_name', 'quantity', 'subfamily_id', 'unit_id', 'price', 'observations')
            ->with(['subfamily:id,name', 'unit:id,name', 'items'])
            ->get()
            ->map(function($field) {
                $field->months = $field->items->pluck('month_id')->toArray();
                return $field;
            });



    $data1 = Field::from('fields as f')
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

        return Inertia::render('Fields', compact('units', 'subfamilies', 'months', 'fields', 'data1', 'season', 'level2s'));
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

    public function getSubfamilies($id)
    {
        $subfamilies = Field::from('fields as f')
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
        $products = Field::from('fields as f')
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

    private function getMonths($fieldId, $quantity, $amount)
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
            $count = DB::table('field_items')
            ->select('field_id')
            ->where('field_id', $fieldId)
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

    // private function getProducts2($subfamilyId)
    // {
    //     $products = Field::from('fields as f')
    //     ->join('field_items as fi', 'f.id', 'fi.field_id')
    //     ->join('units as u', 'f.unit_id', 'u.id')
    //     ->select('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
    //     ->where('f.subfamily_id', $subfamilyId)
    //     ->groupBy('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
    //     ->get()
    //     ->transform(function($value){
    //         $data = $this->getResult2($value);
    //         return [
    //             'id'            => $value->id,
    //             'name'          => $value->product_name,
    //             'unit'          => $value->name ?? '',
    //             'totalQuantity' => $data['totalQuantity'],
    //             'totalAmount'   => $data['totalAmount'],
    //         ];
    //     });

    //     return $products;
    // }

    // private function getResult2($value)
    // {
    //     $totalAmount = 0;
    //     $totalQuantity = 0;
    //     $currentMonth = $this->month_id;
    //     $amountFirst = round($value->price * $value->quantity, 2);

    //     $data = array();

    //     for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
    //         $id = date('n', mktime(0, 0, 0, $x, 1));
    //         array_push($data, $id);
    //     }

    //     foreach($data as $month)
    //     {
    //         $count = DB::table('field_items')
    //         ->select('field_id')
    //         ->where('field_id', $value->id)
    //         ->where('month_id', $month)
    //         ->count();

    //         $amountMonth = $count > 0 ? $amountFirst : 0;
    //         $quantityMonth = $count > 0 ? $value->quantity : 0;
    //         $totalAmount += $amountMonth;
    //         $totalQuantity += $quantityMonth;
    //     }

    //     $this->totalData2 += $totalAmount;

    //     return [
    //         'totalAmount' => number_format($totalAmount, 0, ',', '.'),
    //         'totalQuantity' => number_format($totalQuantity, 2, ',', '.')
    //     ]; 
    // }

    // private function getTotal($id, $team_id)
    // {   
    //     $total =Field::from('fields as f')
    //     ->join('field_items as fi', 'fi.field_id', 'f.id')
    //     ->join('level3s as s', 'f.subfamily_id', 's.id')
    //     ->join('level2s as l2', 's.level2_id', 'l2.id')
    //     ->where('f.team_id', $team_id)
    //     ->where('l2.id', $id)
    //     ->select('fi.field_id')
    //     ->distinct('fi.field_id')
    //     ->count();

    //     return $total;
}

