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
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

use App\Http\Controllers\Traits\BudgetTotalsTrait;

class FieldsController extends Controller
{
    use BudgetTotalsTrait;

    /**
     * Suma el total de fertilizantes para la temporada y equipo dados (price * dosis * meses activos, con conversión de unidades).
     * El cálculo es similar al de agroquímicos pero para fertilizantes.
     */
  


    public $month_id = '';
    public $totalData2 = 0;
    public $totalField = 0;
    public $totalFertilizer = 0;
    public $totalManPower = 0;
    public $totalAgrochemical = 0;
    public $totalSupplies = 0;
    public $totalAdministration = 0;
    public $totalServices = 0;
    public $totalAbsolute = 0;
    public $percentageField = 0;

     /**
     * Suma la columna surface de todos los cost centers de la temporada indicada.
     * @param int $season_id
     * @return float|int
     */
    public function totalsurface($season_id)
    {
        return DB::table('cost_centers')->where('season_id', $season_id)->sum('surface');
    }


    /**
     * Método principal que se invoca para mostrar la vista de campos.
     * Calcula los totales y prepara los datos necesarios para la vista.
     */
    // Este método se invoca cuando se accede a la ruta asociada a este controlador
    // (por ejemplo, /fields).
    // Aquí se obtienen los datos necesarios para la vista de campos, como los totales
    // de cada rubro, los subfamilias, unidades, meses, etc.
    // Luego, se renderiza la vista 'Fields' con los datos obtenidos.
    // También se calcula el porcentaje de fields sobre el total absoluto.
    // Este método es el punto de entrada para la lógica de negocio relacionada con los campos.
    // Se utiliza el trait BudgetTotalsTrait para obtener los totales de otros rubros.
    // Este método es invocado por la ruta definida en routes/web.php o routes/api.php
    // dependiendo de cómo esté configurada la aplicación.
    // El trait BudgetTotalsTrait se utiliza para obtener los totales de otros rubros       

    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $team_id = $user->team_id;

        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season['month_id'];

        // Calcular y asignar los totales globales de cada rubro
        $totalField         = $this->getTotalField($season_id, $team_id);
        $totalFertilizer    = $this->getTotalFertilizer($season_id, $team_id);
        $totalManPower      = $this->getTotalManPower($season_id, $team_id);
        $totalAgrochemical  = $this->getTotalAgrochemical($season_id, $team_id);
        $totalSupplies      = $this->getTotalSupplies($season_id, $team_id);
        $totalAdministration = $this->getTotalAdministration($season_id, $team_id);
        $totalServices      = $this->getTotalServices($season_id, $team_id);

        $totalAbsolute = $totalField + $totalFertilizer + $totalManPower + $totalAgrochemical + $totalSupplies + $totalAdministration + $totalServices;
        $percentageField = $totalAbsolute > 0 ? round(($totalField / $totalAbsolute) * 100, 2) : 0;



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
            ->get()->transform(function ($subfamily) {
                return [
                    'label' => $subfamily->name,
                    'value' => $subfamily->id
                ];
            });

 // --- Data3 agrupado: Gastos por Hectarea agrupado por level2, level3, producto ---
        $surfaceTotal = $this->totalsurface($season_id);
        $data3Raw = \App\Models\Field::from('fields as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.price', 'f.quantity', 'f.unit_id', 'u.name as unit_name', 's.name as level3_name', 'l2.name as level2_name')
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
                    $count = DB::table('field_items')
                        ->where('field_id', $value->id)
                        ->where('month_id', $id)
                        ->count();
                    $amountMonth = $count > 0 ? $amount : 0;
                    $monthsArr[] = number_format($amountMonth, 0, '', '');
                }
                return [
                    'level2'    => $value->level2_name,
                    'level3'    => $value->level3_name,
                    'product'   => $value->product_name,
                    'quantity'  => number_format($quantity, 0, '', ''),
                    'total'     => number_format($amount, 0, '', ''),
                    'months'    => $monthsArr
                ];
            });

        // Agrupar por level2, luego level3, luego producto
        $data3 = [];
        foreach ($data3Raw as $item) {
            $level2Name = $item['level2'];
            $level3Name = $item['level3'];
            $productName = $item['product'];
            if (!isset($data3[$level2Name])) {
                $data3[$level2Name] = [
                    'name' => $level2Name,
                    'level3s' => []
                ];
            }
            if (!isset($data3[$level2Name]['level3s'][$level3Name])) {
                $data3[$level2Name]['level3s'][$level3Name] = [
                    'name' => $level3Name,
                    'products' => []
                ];
            }
            if (!isset($data3[$level2Name]['level3s'][$level3Name]['products'][$productName])) {
                $data3[$level2Name]['level3s'][$level3Name]['products'][$productName] = [
                    'name' => $productName,
                    'items' => []
                ];
            }
            $data3[$level2Name]['level3s'][$level3Name]['products'][$productName]['items'][] = [
                'quantity' => $item['quantity'],
                'total'    => $item['total'],
                'months'   => $item['months']
            ];
        }
        // Convertir a arrays indexados para Vue
        $data3 = array_values(array_map(function($level2) {
            $level2['level3s'] = array_values(array_map(function($level3) {
                $level3['products'] = array_values($level3['products']);
                return $level3;
            }, $level2['level3s']));
            return $level2;
        }, $data3));
       





        $subfamilies = collect();
        if ($level1) {
            $subfamilies = Level3::whereHas('level2', function ($query) use ($level1) {
                $query->where('level1_id', $level1->id);
            })
                ->get()
                ->transform(function ($subfamily) {
                    return [
                        'label' => $subfamily->name,
                        'value' => $subfamily->id
                    ];
                });
        }


        $units = Unit::get()->transform(function ($unit) {
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
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->paginate(10)
            ->through(function ($field) {
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
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->get()
            ->map(function ($field) {
                $field->months = $field->items->pluck('month_id')->toArray();
                return $field;
            });
        $data1 = Field::from('fields as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('l2.id', 'l2.name')
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('l2.id', 'l2.name')
            ->get()
            ->transform(function ($value) use ($team_id, $season_id) {
                return [
                    'id'           => $value->id,
                    'name'         => $value->name,
                    'subfamilies'  => $this->getSubfamilies($value->id, $team_id, $season_id)
                ];
            });
        $data2 = Level3::get()->map(function ($subfamily) use ($team_id, $season_id) {
            $products = $this->getProducts2($subfamily->id, $team_id, $season_id);
            if ($products->count() > 0) {
                return [
                    'name' => $subfamily->name,
                    'products' => $products
                ];
            }
            return null;
        })->filter()->values();
        // $percentageField ya está calculado correctamente arriba, no sobrescribirlo aquí
        return Inertia::render('Fields', compact(
            'units',
            'subfamilies',
            'months',
            'fields',
            'data1',
            'data2',
            'data3',
            'season',
            'level2s',
            'team_id',
            'season_id',
            'percentageField' // Pasar el porcentaje de fields al frontend
        ));
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

    public function getSubfamilies($id, $team_id, $season_id)
    {
        $subfamilies = Field::from('fields as f')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->where('l2.id', $id)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->select('s.id', 's.name')
            ->groupBy('s.id', 's.name')
            ->get()
            ->transform(function ($subfamily) use ($team_id, $season_id) {
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
        $products = Field::from('fields as f')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
            ->where('f.subfamily_id', $id)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('f.id', 'f.product_name', 'f.quantity', 'f.price', 'f.unit_id', 'u.name')
            ->get()
            ->transform(function ($value) use ($team_id, $season_id) {
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
        foreach ($data as $month) {
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

    private function getProducts2($subfamilyId, $team_id, $season_id)
    {
        $products = Field::from('fields as f')
            ->join('field_items as fi', 'f.id', 'fi.field_id')
            ->join('units as u', 'f.unit_id', 'u.id')
            ->select('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
            ->where('f.subfamily_id', $subfamilyId)
            ->where('f.team_id', $team_id)
            ->where('f.season_id', $season_id)
            ->groupBy('f.id', 'f.product_name', 'f.price', 'f.quantity', 'u.name')
            ->get()
            ->transform(function ($value) use ($team_id, $season_id) {
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

        foreach ($data as $month) {
            $count = DB::table('field_items')
                ->select('field_id')
                ->where('field_id', $value->id)
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
        $total = Field::from('fields as f')
            ->join('field_items as fi', 'fi.field_id', 'f.id')
            ->join('level3s as s', 'f.subfamily_id', 's.id')
            ->join('level2s as l2', 's.level2_id', 'l2.id')
            ->where('f.team_id', $team_id)
            ->where('l2.id', $id)
            ->select('fi.field_id')
            ->distinct('fi.field_id')
            ->count();

        return $total;
    }
}
