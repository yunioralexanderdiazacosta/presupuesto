<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Budget;
use App\Models\Season;
use App\Models\CostCenter;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Supply;
use App\Models\Service;
use Inertia\Inertia;
use App\Models\Fruit;
use App\Services\WeatherService;
use Spatie\Permission\Traits\HasRoles;

/**
 * Controlador principal del Dashboard.
 * Calcula y agrupa los datos necesarios para mostrar los gráficos y tablas del dashboard.
 * Incluye funciones auxiliares para obtener totales, agrupaciones y métricas por estado de desarrollo y por hectárea.
 */
class TechnicalPanelController extends Controller
{
    use HasRoles;
    use \App\Http\Controllers\Traits\BudgetTotalsTrait;
    public $month_id = '';

    public $totalAgrochemical = 0;


    public $totalFertilizer = 0;

    public $totalManPower = 0;

    public $totalSupplies = 0;

    public $totalServices = 0;

    public $monthsAgrochemical = [];

    public $monthsFertilizer = [];

    public $monthsManPower = [];

    public $monthsSupplies = [];

    public $monthsServices = [];

    /**
     * Acción principal: Renderiza el dashboard con todos los datos agregados y procesados.
     * - Calcula totales por rubro y por mes.
     * - Obtiene datos para gráficos de torta y barras.
     * - Calcula métricas por estado de desarrollo y por hectárea.
     * - Integra datos de clima.
     */

 /**
     * Devuelve la cantidad de registros de cada entidad principal, filtrados por season_id y team_id.
     * @param int $season_id
     * @param int $team_id
     * @return array
     */
    public static function getEntityCounts($season_id, $team_id)
    {
        $agrochemicals = \App\Models\Agrochemical::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $fertilizers = \App\Models\Fertilizer::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $manpowers = \App\Models\ManPower::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $supplies = \App\Models\Supply::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $services = \App\Models\Service::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $fields = \App\Models\Field::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        $administrations = \App\Models\Administration::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->count();
        return [
            'agrochemicals' => $agrochemicals,
            'fertilizers' => $fertilizers,
            'manpowers' => $manpowers,
            'supplies' => $supplies,
            'services' => $services,
            'fields' => $fields,
            'administrations' => $administrations,
        ];
    }

    /**
     * Obtiene los totales de cada rubro principal y el porcentaje que representa cada uno respecto al total general.
     * Devuelve un array de la forma:
     * [
     *   [ 'label' => 'Campo', 'total' => 1000, 'percent' => 10.5 ], ...
     * ]
     */
    public function getMainBudgetTotalsAndPercents($season_id, $team_id)
    {
        // Usar los métodos del trait BudgetTotalsTrait
        $totalField = (float) $this->getTotalField($season_id, $team_id);
        $totalAdministration = (float) $this->getTotalAdministration($season_id, $team_id);
        $totalFertilizer = (float) $this->getTotalFertilizer($season_id, $team_id);
        $totalManPower = (float) $this->getTotalManPower($season_id, $team_id);
        $totalAgrochemical = (float) $this->getTotalAgrochemical($season_id, $team_id);
        $totalSupplies = (float) $this->getTotalSupplies($season_id, $team_id);
        $totalServices = (float) $this->getTotalServices($season_id, $team_id);

        $labels = [
            'Generales Campo',
            'Administración',
            'Fertilizantes',
            'Mano de Obra',
            'Agroquímicos',
            'Insumos',
            'Servicios',
        ];
        $totals = [
            $totalField,
            $totalAdministration,
            $totalFertilizer,
            $totalManPower,
            $totalAgrochemical,
            $totalSupplies,
            $totalServices,
        ];
        $grandTotal = array_sum($totals);
        $result = [];
        foreach ($labels as $i => $label) {
            $total = $totals[$i];
            $percent = $grandTotal > 0 ? round(($total / $grandTotal) * 100, 2) : 0;
            $result[] = [
                'label' => $label,
                'total' => $total,
                'percent' => $percent
            ];
        }
        return $result;
    }


    public function __invoke(Request $request, WeatherService $weatherService)
    {
        $user = Auth::user();
        //Si es super admin
        if($user->hasRole('Super Admin')){
            return Inertia::render('Dashboard2');
        //Si es otro rol
        } else {

            $season_id = session('season_id');
        $season = Season::select('name', 'month_id')->where('id', $season_id)->first();

        $this->month_id = $season ? $season['month_id'] : 1;
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
        $costCenters = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });
        $costCentersId = $costCenters->pluck('value');
        $this->getAgrochemicalProducts($costCentersId);
        $this->getFertilizerProducts($costCentersId);
        $this->getManPowerProducts($costCentersId);
        $this->getServicesProducts($costCentersId);
        $this->getSuppliesProducts($costCentersId);
        $pieLabels = ['Agroquimicos', 'Fertilizantes', 'Mano de obra', 'Servicios', 'Insumos'];
        $pieDatasets = [
            [
                "data" => [round($this->totalAgrochemical), round($this->totalFertilizer), round($this->totalManPower), round($this->totalServices), round($this->totalSupplies)],
                "backgroundColor" => ['#36a2eb', '#ff6384', '#ffce56', '#008000', '#FF2C2C'],
                "hoverOffset" => 4,
                "cutout" => 0
            ]
        ];
        // Calcular totales de administración y fields
        $totalAdministration = $this->getAdministrationTotalsByLevel12($user->team_id)->sum('total_amount');
        $totalFields = $this->getFieldTotalsByLevel12($user->team_id)->sum('total_amount');
        $totalSeason = number_format(($this->totalAgrochemical + $this->totalFertilizer + $this->totalManPower + $this->totalServices + $this->totalSupplies + $totalAdministration + $totalFields), 0, ',', '.');
        $totalAgrochemical = number_format($this->totalAgrochemical, 0, ',', '.');
        $totalFertilizer = number_format($this->totalFertilizer, 0, ',', '.');
        $totalManPower = number_format($this->totalManPower, 0, ',', '.');
        $totalServices = number_format($this->totalServices, 0, ',', '.');
        $totalSupplies = number_format($this->totalSupplies, 0, ',', '.');

        // NUEVO: Calcular y formatear los meses de administración y fields
        $monthsAdministrationRaw = $this->getMonthsAdministration($user->team_id);
        $monthsFieldsRaw = $this->getMonthsFields($user->team_id);
        $monthsAdministration = [];
        foreach($monthsAdministrationRaw as $key => $value){
            $monthsAdministration[$key] = number_format($value, 0, ',', '.');
        }
        $monthsFields = [];

        foreach($monthsFieldsRaw as $key => $value){
            $monthsFields[$key] = number_format($value, 0, ',', '.');
        }

        // Asegurar que monthsAdministration y monthsFields tengan SIEMPRE los 12 meses (1-12) como claves
        $allMonthsAdministration = [];
        $allMonthsFields = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsAdministration[$key] = isset($monthsAdministration[$key]) ? $monthsAdministration[$key] : '0';
            $allMonthsFields[$key] = isset($monthsFields[$key]) ? $monthsFields[$key] : '0';
        }
        $monthsAdministration = $allMonthsAdministration;
        $monthsFields = $allMonthsFields;

        $monthsAgrochemical = [];
        foreach($this->monthsAgrochemical as $key => $value){
            $monthsAgrochemical[$key] = number_format($value, 0, ',','.');
        }
        $monthsFertilizer = [];
        foreach($this->monthsFertilizer as $key => $value){
            $monthsFertilizer[$key] = number_format($value, 0, ',','.');
        }
        $monthsManPower = [];
        foreach($this->monthsManPower as $key => $value){
            $monthsManPower[$key] = number_format($value, 0, ',','.');
        }
        $monthsServices = [];
        foreach($this->monthsServices as $key => $value){
            $monthsServices[$key] = number_format($value, 0, ',','.');
        }
        $monthsSupplies = [];
        foreach($this->monthsSupplies as $key => $value){
            $monthsSupplies[$key] = number_format($value, 0, ',','.');
        }
        // Weather integration
        $city = $request->input('city') ?? $request->input('weatherCity') ?? 'Curico, Chile'; // Usa la ciudad enviada por el frontend o la default
        $weather = $weatherService->getCurrentWeather($city);
        
        // Calcular totales de agroquímicos por estado de desarrollo (agrupado por fruit_id y development_state_id)
        $agrochemicalByDevState = [];
        $agrochemicals = Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->whereIn('ai.cost_center_id', $costCentersId)
            ->groupBy('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->get();
        foreach ($agrochemicals as $agrochemical) {
            $byDev = $this->getAgrochemicalResultByDevelopmentState($agrochemical, $costCentersId);
            if (is_array($byDev) || is_object($byDev)) {
                foreach ($byDev as $fruitId => $devStates) {
                    foreach ($devStates as $devStateId => $amount) {
                        $fruitIdStr = (string)$fruitId;
                        $devStateIdStr = (string)$devStateId;
                        if (!isset($agrochemicalByDevState[$fruitIdStr])) {
                            $agrochemicalByDevState[$fruitIdStr] = [];
                        }
                        if (!isset($agrochemicalByDevState[$fruitIdStr][$devStateIdStr])) {
                            $agrochemicalByDevState[$fruitIdStr][$devStateIdStr] = 0;
                        }
                        $agrochemicalByDevState[$fruitIdStr][$devStateIdStr] += $amount;
                    }
                }
            }
        }
        // Calcular gasto por hectárea de agroquímicos por estado de desarrollo
        $agrochemicalExpensePerHectare = [];
        foreach ($agrochemicals as $agrochemical) {
            $byDev = $this->getAgrochemicalExpensePerHectareByDevelopmentState($agrochemical, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($agrochemicalExpensePerHectare[$fruitIdStr])) {
                        $agrochemicalExpensePerHectare[$fruitIdStr] = [];
                    }
                    if (!isset($agrochemicalExpensePerHectare[$fruitIdStr][$devStateIdStr])) {
                        $agrochemicalExpensePerHectare[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $agrochemicalExpensePerHectare[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular totales de fertilizantes por estado de desarrollo (agrupado por fruit_id y development_state_id)
        $fertilizerByDevState = [];
        $fertilizers = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->whereIn('fi.cost_center_id', $costCentersId)
            ->groupBy('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->get();
        foreach ($fertilizers as $fertilizer) {
            $byDev = $this->getFertilizerResultByDevelopmentState($fertilizer, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($fertilizerByDevState[$fruitIdStr])) {
                        $fertilizerByDevState[$fruitIdStr] = [];
                    }
                    if (!isset($fertilizerByDevState[$fruitIdStr][$devStateIdStr])) {
                        $fertilizerByDevState[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $fertilizerByDevState[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular gasto por hectárea de fertilizantes por estado de desarrollo
        $fertilizerExpensePerHectare = [];
        foreach ($fertilizers as $fertilizer) {
            $byDev = $this->getFertilizerExpensePerHectareByDevelopmentState($fertilizer, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($fertilizerExpensePerHectare[$fruitIdStr])) {
                        $fertilizerExpensePerHectare[$fruitIdStr] = [];
                    }
                    if (!isset($fertilizerExpensePerHectare[$fruitIdStr][$devStateIdStr])) {
                        $fertilizerExpensePerHectare[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $fertilizerExpensePerHectare[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular totales de mano de obra por estado de desarrollo (agrupado por fruit_id y development_state_id)
        $manPowerByDevState = [];
        $manPowers = ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->select('mp.id', 'mp.price', 'mp.workday')
            ->whereIn('mpi.cost_center_id', $costCentersId)
            ->groupBy('mp.id', 'mp.price', 'mp.workday')
            ->get();
        foreach ($manPowers as $manPower) {
            $byDev = $this->getManPowerResultByDevelopmentState($manPower, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($manPowerByDevState[$fruitIdStr])) {
                        $manPowerByDevState[$fruitIdStr] = [];
                    }
                    if (!isset($manPowerByDevState[$fruitIdStr][$devStateIdStr])) {
                        $manPowerByDevState[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $manPowerByDevState[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular gasto por hectárea de mano de obra por estado de desarrollo
        $manPowerExpensePerHectare = [];
        foreach ($manPowers as $manPower) {
            $byDev = $this->getManPowerExpensePerHectareByDevelopmentState($manPower, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($manPowerExpensePerHectare[$fruitIdStr])) {
                        $manPowerExpensePerHectare[$fruitIdStr] = [];
                    }
                    if (!isset($manPowerExpensePerHectare[$fruitIdStr][$devStateIdStr])) {
                        $manPowerExpensePerHectare[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $manPowerExpensePerHectare[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular totales de servicios por estado de desarrollo (agrupado por fruit_id y development_state_id)
        $servicesByDevState = [];
        $services = Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->get();
        foreach ($services as $service) {
            $byDev = $this->getServiceResultByDevelopmentState($service, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($servicesByDevState[$fruitIdStr])) {
                        $servicesByDevState[$fruitIdStr] = [];
                    }
                    if (!isset($servicesByDevState[$fruitIdStr][$devStateIdStr])) {
                        $servicesByDevState[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $servicesByDevState[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular gasto por hectárea de servicios por estado de desarrollo
        $servicesExpensePerHectare = [];
        foreach ($services as $service) {
            $byDev = $this->getServiceExpensePerHectareByDevelopmentState($service, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($servicesExpensePerHectare[$fruitIdStr])) {
                        $servicesExpensePerHectare[$fruitIdStr] = [];
                    }
                    if (!isset($servicesExpensePerHectare[$fruitIdStr][$devStateIdStr])) {
                        $servicesExpensePerHectare[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $servicesExpensePerHectare[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular totales de insumos por estado de desarrollo
        $suppliesByDevState = [];
        $supplies = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
            ->get();
        foreach ($supplies as $supply) {
            $byDev = $this->getSupplyResultByDevelopmentState($supply, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($suppliesByDevState[$fruitIdStr])) {
                        $suppliesByDevState[$fruitIdStr] = [];
                    }
                    if (!isset($suppliesByDevState[$fruitIdStr][$devStateIdStr])) {
                        $suppliesByDevState[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $suppliesByDevState[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Calcular gasto por hectárea de insumos por estado de desarrollo
        $suppliesExpensePerHectare = [];
        foreach ($supplies as $supply) {
            $byDev = $this->getSupplyExpensePerHectareByDevelopmentState($supply, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($suppliesExpensePerHectare[$fruitIdStr])) {
                        $suppliesExpensePerHectare[$fruitIdStr] = [];
                    }
                    if (!isset($suppliesExpensePerHectare[$fruitIdStr][$devStateIdStr])) {
                        $suppliesExpensePerHectare[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $suppliesExpensePerHectare[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        // Obtener nombres de estados de desarrollo
        $devStates = \App\Models\DevelopmentState::all(['id', 'name'])->keyBy('id')->toArray();

        // Obtener totales de administración por Level1 y Level2 (sin season_id)
        $administrationTotalsByLevel12 = $this->getAdministrationTotalsByLevel12($user->team_id);
        $fieldTotalsByLevel12 = $this->getFieldTotalsByLevel12($user->team_id);
        $totalsByLevel12 = $this->getTotalsByLevel12($user->team_id);

        // Calcular el total de superficie de todos los cost centers de la temporada
        $totalSurface = \App\Models\CostCenter::where('season_id', $season_id)->sum('surface');
        $entityCounts = self::getEntityCounts($season_id, $user->team_id);
        // Calcular los totales y porcentajes de cada rubro principal
        $mainTotalsAndPercents = $this->getMainBudgetTotalsAndPercents($season_id, $user->team_id);
        // Construir fruitsMap y pasarlo al frontend
        $fruitsMap = $this->getFruitsMap($user->team_id);
        return Inertia::render('TechnicalPanel', compact(
            'totalSeason', 'pieLabels', 'pieDatasets',
            'monthsAgrochemical', 'totalAgrochemical',
            'monthsFertilizer', 'totalFertilizer',
            'monthsManPower', 'totalManPower',
            'totalServices', 'monthsServices',
            'totalSupplies', 'monthsSupplies',
            'monthsAdministration', 'monthsFields',
            'months', 'weather', 'city',
            'agrochemicalByDevState',
            'fertilizerByDevState',
            'manPowerByDevState',
            'servicesByDevState',
            'suppliesByDevState',
            'agrochemicalExpensePerHectare',
            'fertilizerExpensePerHectare',
            'manPowerExpensePerHectare',
            'servicesExpensePerHectare',
            'suppliesExpensePerHectare',
            'devStates',
            'administrationTotalsByLevel12',
            'fieldTotalsByLevel12',
            'totalsByLevel12',
            'entityCounts',
            'totalSurface',
            'mainTotalsAndPercents', // <-- nuevo prop para los gauges
            'fruitsMap'
        ));


        }
    }

    /**
     * Construye el mapeo [fruitId => fruitName] para la vista
     */
    private function getFruitsMap($team_id = null) {
        $query = Fruit::query();
        if ($team_id) {
            $query->where('team_id', $team_id);
        }
        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Obtiene y acumula los totales de agroquímicos por cost center y por mes.
     * Actualiza las propiedades $this->totalAgrochemical y $this->monthsAgrochemical.
     * No retorna datos útiles, solo realiza side-effects.
     */
    private function getAgrochemicalProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por agrochemical_id, cost_center_id, month_id
        $items = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = Agrochemical::from('agrochemicals as a')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->whereIn('a.id', $items->pluck('agrochemical_id')->unique())
            ->get();

        // Traer superficies de los cost centers de una vez
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Indexar items por agrochemical_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->agrochemical_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getAgrochemicalResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
        return $products;
    }

    /**
     * Calcula el monto total de un producto agroquímico en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getAgrochemicalResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
    {
        $totalAmount = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            if ($value->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmount += $amountMonth;
                if (!isset($this->monthsAgrochemical[$month])) {
                    $this->monthsAgrochemical[$month] = 0;
                }
                $this->monthsAgrochemical[$month] += $amountMonth;
            }
        }
        $this->totalAgrochemical += $totalAmount;
    }

    /**
     * Obtiene y acumula los totales de fertilizantes por cost center y por mes.
     * Actualiza las propiedades $this->totalFertilizer y $this->monthsFertilizer.
     */
    private function getFertilizerProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por fertilizer_id, cost_center_id, month_id
        $items = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = Fertilizer::from('fertilizers as f')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->whereIn('f.id', $items->pluck('fertilizer_id')->unique())
            ->get();

        // Traer superficies de los cost centers de una vez
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Indexar items por fertilizer_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->fertilizer_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getFertilizerResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
        return $products;
    }

    /**
     * Calcula el monto total de un fertilizante en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getFertilizerResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
    {
        $totalAmount = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmount += $amountMonth;
                if (!isset($this->monthsFertilizer[$month])) {
                    $this->monthsFertilizer[$month] = 0;
                }
                $this->monthsFertilizer[$month] += $amountMonth;
            }
        }
        $this->totalFertilizer += $totalAmount;
    }

    /**
     * Obtiene y acumula los totales de mano de obra por cost center y por mes.
     * Actualiza las propiedades $this->totalManPower y $this->monthsManPower.
     */
    private function getManPowerProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por man_power_id, cost_center_id, month_id
        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = ManPower::from('man_powers as mp')
            ->leftJoin('units as u', 'mp.unit_id', 'u.id')
            ->select('mp.id', 'mp.price', 'mp.workday')
            ->whereIn('mp.id', $items->pluck('man_power_id')->unique())
            ->get();

        // Traer superficies de los cost centers de una vez
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Indexar items por man_power_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->man_power_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getManPowerResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
        return $products;
    }

    /**
     * Calcula el monto total de mano de obra en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getManPowerResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
    {
        $totalAmount = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmount += $amountMonth;
                if (!isset($this->monthsManPower[$month])) {
                    $this->monthsManPower[$month] = 0;
                }
                $this->monthsManPower[$month] += $amountMonth;
            }
        }
        $this->totalManPower += $totalAmount;
    }


    /**
     * Obtiene y acumula los totales de servicios por cost center y por mes.
     * Actualiza las propiedades $this->totalServices y $this->monthsServices.
     */
    private function getServicesProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por service_id, cost_center_id, month_id
        $items = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = Service::from('services as s')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price',  'u.name')
            ->whereIn('s.id', $items->pluck('service_id')->unique())
            ->get();

        // Traer superficies de los cost centers de una vez
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Indexar items por service_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->service_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getServicesResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
        return $products;
    }

    /**
     * Calcula el monto total de un servicio en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getServicesResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
    {
        $totalAmount = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmount += $amountMonth;
                if (!isset($this->monthsServices[$month])) {
                    $this->monthsServices[$month] = 0;
                }
                $this->monthsServices[$month] += $amountMonth;
            }
        }
        $this->totalServices += $totalAmount;
    }

    /**
     * Obtiene y acumula los totales de insumos por cost center y por mes.
     * Actualiza las propiedades $this->totalSupplies y $this->monthsSupplies.
     */
    private function getSuppliesProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por supply_id, cost_center_id, month_id
        $items = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = Supply::from('supplies as s')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price',  'u.name')
            ->whereIn('s.id', $items->pluck('supply_id')->unique())
            ->get();

        // Traer superficies de los cost centers de una vez
        $surfaces = \App\Models\CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Indexar items por supply_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->supply_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getSuppliesResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
        return $products;
    }

    /**
     * Calcula el monto total de un insumo en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getSuppliesResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
    {
        $totalAmount = 0;
        foreach ($costCentersId as $costCenter) {
            $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmount += $amountMonth;
                if (!isset($this->monthsSupplies[$month])) {
                    $this->monthsSupplies[$month] = 0;
                }
                $this->monthsSupplies[$month] += $amountMonth;
            }
        }
        $this->totalSupplies += $totalAmount;
    }

    /**
     * Calcula el total de agroquímicos por estado de desarrollo.
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getAgrochemicalResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Traer cost centers y superficies
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        // Consulta agregada: contar items por agrochemical_id, cost_center_id, month_id
        $items = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('agrochemical_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get();
        // Calcular totales por fruit_id y development_state_id
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            $surface = $center['surface'];
            if ($value->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr])) {
                $result[$fruitIdStr] = [];
            }
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $amountMonth;
        }
        return $result;
    }

    /**
     * Calcula el gasto promedio de agroquímicos por hectárea y estado de desarrollo.
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getAgrochemicalExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Traer cost centers y superficies
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        // Consulta agregada: contar items por agrochemical_id, cost_center_id, month_id
        $items = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('agrochemical_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get();
        // Calcular totales por fruit_id y development_state_id
        $amounts = [];
        $surfaces = [];
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            $surface = $center['surface'];
            if ($value->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($amounts[$fruitIdStr][$devStateIdStr])) {
                $amounts[$fruitIdStr][$devStateIdStr] = 0;
                $surfaces[$fruitIdStr][$devStateIdStr] = 0;
            }
            $amounts[$fruitIdStr][$devStateIdStr] += $amountMonth;
            $surfaces[$fruitIdStr][$devStateIdStr] += $surface;
        }
        foreach ($amounts as $fruitId => $devStates) {
            foreach ($devStates as $devStateId => $amount) {
                $surface = $surfaces[$fruitId][$devStateId];
                $result[$fruitId][$devStateId] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
    }

    /**
     * Calcula el total de fertilizantes por estado de desarrollo.
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getFertilizerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('fertilizer_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get();
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr])) {
                $result[$fruitIdStr] = [];
            }
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $amountMonth;
        }
        return $result;
    }

    /**
     * Calcula el gasto promedio de fertilizantes por hectárea y estado de desarrollo.
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getFertilizerExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('fertilizer_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get();
        $amounts = [];
        $surfaces = [];
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($amounts[$fruitIdStr][$devStateIdStr])) {
                $amounts[$fruitIdStr][$devStateIdStr] = 0;
                $surfaces[$fruitIdStr][$devStateIdStr] = 0;
            }
            $amounts[$fruitIdStr][$devStateIdStr] += $amountMonth;
            $surfaces[$fruitIdStr][$devStateIdStr] += $surface;
        }
        foreach ($amounts as $fruitId => $devStates) {
            foreach ($devStates as $devStateId => $amount) {
                $surface = $surfaces[$fruitId][$devStateId];
                $result[$fruitId][$devStateId] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
    }

    /**
     * Calcula el total de mano de obra separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getManPowerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('man_power_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get();
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr])) {
                $result[$fruitIdStr] = [];
            }
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $amountMonth;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de mano de obra por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getManPowerExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('man_power_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get();
        $amounts = [];
        $surfaces = [];
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($amounts[$fruitIdStr][$devStateIdStr])) {
                $amounts[$fruitIdStr][$devStateIdStr] = 0;
                $surfaces[$fruitIdStr][$devStateIdStr] = 0;
            }
            $amounts[$fruitIdStr][$devStateIdStr] += $amountMonth;
            $surfaces[$fruitIdStr][$devStateIdStr] += $surface;
        }
        foreach ($amounts as $fruitId => $devStates) {
            foreach ($devStates as $devStateId => $amount) {
                $surface = $surfaces[$fruitId][$devStateId];
                $result[$fruitId][$devStateId] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de servicios separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getServiceResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('service_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get();
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr])) {
                $result[$fruitIdStr] = [];
            }
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $amountMonth;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de servicios por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getServiceExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('service_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get();
        $amounts = [];
        $surfaces = [];
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($amounts[$fruitIdStr][$devStateIdStr])) {
                $amounts[$fruitIdStr][$devStateIdStr] = 0;
                $surfaces[$fruitIdStr][$devStateIdStr] = 0;
            }
            $amounts[$fruitIdStr][$devStateIdStr] += $amountMonth;
            $surfaces[$fruitIdStr][$devStateIdStr] += $surface;
        }
        foreach ($amounts as $fruitId => $devStates) {
            foreach ($devStates as $devStateId => $amount) {
                $surface = $surfaces[$fruitId][$devStateId];
                $result[$fruitId][$devStateId] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
    }

    /**
     * Obtiene el totalAmount de insumos separado por development_state
     * Devuelve un array: [development_state_id => totalAmount]
     */
    private function getSupplyResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('supply_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get();
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr])) {
                $result[$fruitIdStr] = [];
            }
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $amountMonth;
        }
        return $result;
    }

    /**
     * Obtiene el gasto promedio de insumos por hectárea separado por development_state
     * Devuelve un array: [development_state_id => gastoPorHectarea]
     */
    private function getSupplyExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $centerData = [];
        foreach ($costCenters as $c) {
            $centerData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        $items = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->where('supply_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get();
        $amounts = [];
        $surfaces = [];
        foreach ($items as $item) {
            $center = $centerData[$item->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $amountMonth = $item->count > 0 ? $amountFirst : 0;
            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($amounts[$fruitIdStr][$devStateIdStr])) {
                $amounts[$fruitIdStr][$devStateIdStr] = 0;
                $surfaces[$fruitIdStr][$devStateIdStr] = 0;
            }
            $amounts[$fruitIdStr][$devStateIdStr] += $amountMonth;
            $surfaces[$fruitIdStr][$devStateIdStr] += $surface;
        }
        foreach ($amounts as $fruitId => $devStates) {
            foreach ($devStates as $devStateId => $amount) {
                $surface = $surfaces[$fruitId][$devStateId];
                $result[$fruitId][$devStateId] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
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

    /**
     * Obtiene los totales de administración agrupados por Level 1 y Level 2.
     * Devuelve una colección con: [level1_id, level1_name, level2_id, level2_name, total_amount]
     */
    private function getAdministrationTotalsByLevel12($team_id = null)
    {
        $season_id = session('season_id');
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Traer todas las administraciones y sus datos
        $administrations = DB::table('administrations as a')
            ->join('level3s as l3', 'a.subfamily_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->select(
                'l1.id as level1_id', 'l1.name as level1_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'a.id as administration_id', 'a.price', 'a.quantity', 'a.unit_id'
            )
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        $administrations = $administrations->get();

        // Traer todos los administration_items de una vez
        $items = DB::table('administration_items')
            ->select('administration_id', 'month_id')
            ->whereIn('month_id', $months)
            ->whereIn('administration_id', $administrations->pluck('administration_id'))
            ->get();

        // Indexar items por administration_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->administration_id][$item->month_id] = true;
        }

        $totals = [];
        foreach ($administrations as $adm) {
            $activeMonths = isset($itemIndex[$adm->administration_id]) ? count($itemIndex[$adm->administration_id]) : 0;
            if ($activeMonths > 0) {
                $key = $adm->level1_id . '-' . $adm->level2_id;
                if (!isset($totals[$key])) {
                    $totals[$key] = [
                        'level1_id' => $adm->level1_id,
                        'level1_name' => $adm->level1_name,
                        'level2_id' => $adm->level2_id,
                        'level2_name' => $adm->level2_name,
                        'total_amount' => 0
                    ];
                }
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity * $activeMonths, 2);
                $totals[$key]['total_amount'] += $amount;
            }
        }
        return collect(array_values($totals));
    }



    private function getFieldTotalsByLevel12($team_id = null)
    {
        $season_id = session('season_id');
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $fields = DB::table('fields as a')
            ->join('level3s as l3', 'a.subfamily_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->select(
                'l1.id as level1_id', 'l1.name as level1_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'a.id as field_id', 'a.price', 'a.quantity', 'a.unit_id'
            )
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        $fields = $fields->get();

        // Traer todos los field_items de una vez
        $items = DB::table('field_items')
            ->select('field_id', 'month_id')
            ->whereIn('month_id', $months)
            ->whereIn('field_id', $fields->pluck('field_id'))
            ->get();

        // Indexar items por field_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->field_id][$item->month_id] = true;
        }

        $totals = [];
        foreach ($fields as $adm) {
            $activeMonths = isset($itemIndex[$adm->field_id]) ? count($itemIndex[$adm->field_id]) : 0;
            if ($activeMonths > 0) {
                $key = $adm->level1_id . '-' . $adm->level2_id;
                if (!isset($totals[$key])) {
                    $totals[$key] = [
                        'level1_id' => $adm->level1_id,
                        'level1_name' => $adm->level1_name,
                        'level2_id' => $adm->level2_id,
                        'level2_name' => $adm->level2_name,
                        'total_amount' => 0
                    ];
                }
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity * $activeMonths, 2);
                $totals[$key]['total_amount'] += $amount;
            }
        }
        return collect(array_values($totals));
    }

    /**
     * Obtiene los totales generales (agroquímicos, fertilizantes, mano de obra, servicios, insumos) agrupados por Level 1 y Level 2.
     * Devuelve una colección con: [level1_id, level1_name, level2_id, level2_name, total_amount]
     */
    private function getTotalsByLevel12($team_id = null)
    {
        $season_id = session('season_id');
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $costCenters = \App\Models\CostCenter::where('season_id', $season_id);
        if ($team_id) {
            $costCenters->whereHas('season.team', function($query) use ($team_id){
                $query->where('team_id', $team_id);
            });
        }
        $costCenters = $costCenters->pluck('id');
        $totals = [];
        $addTotal = function($level1_id, $level1_name, $level2_id, $level2_name, $amount) use (&$totals) {
            $key = $level1_id.'-'.$level2_id;
            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'level1_id' => $level1_id,
                    'level1_name' => $level1_name,
                    'level2_id' => $level2_id,
                    'level2_name' => $level2_name,
                    'total_amount' => 0
                ];
            }
            $totals[$key]['total_amount'] += $amount;
        };
        // AGROCHEMICALS
        $agrochemicals = \App\Models\Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->join('level3s as l3', 'a.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('a.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'ai.cost_center_id')
            ->whereIn('ai.cost_center_id', $costCenters)
            ->groupBy('a.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'ai.cost_center_id')
            ->get();
        foreach ($agrochemicals as $a) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface')->where('id', $a->cost_center_id)->first();
            $dose = (($a->unit_id == 4 && $a->unit_id_price == 3) || ($a->unit_id == 2 && $a->unit_id_price == 1)) ? ($a->dose / 1000) : $a->dose;
            $surface = $first ? $first->surface : 0;
            if ($a->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($a->dose_type_id == 2) {
                $quantityFirst = round((($a->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($a->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('agrochemical_items')
                    ->where('agrochemical_id', $a->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $a->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($a->level1_id, $a->level1_name, $a->level2_id, $a->level2_name, $amount);
        }
        // FERTILIZERS
        $fertilizers = \App\Models\Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('level3s as l3', 'f.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('f.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'fi.cost_center_id')
            ->whereIn('fi.cost_center_id', $costCenters)
            ->groupBy('f.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'fi.cost_center_id')
            ->get();
        foreach ($fertilizers as $f) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface')->where('id', $f->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $dose = (($f->unit_id == 4 && $f->unit_id_price == 3) || ($f->unit_id == 2 && $f->unit_id_price == 1)) ? ($f->dose / 1000) : $f->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($f->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('fertilizer_items')
                    ->where('fertilizer_id', $f->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $f->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($f->level1_id, $f->level1_name, $f->level2_id, $f->level2_name, $amount);
        }
        // MANPOWER
        $manpowers = \App\Models\ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->join('level3s as l3', 'mp.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('mp.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'mpi.cost_center_id')
            ->whereIn('mpi.cost_center_id', $costCenters)
            ->groupBy('mp.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'mpi.cost_center_id')
            ->get();
        foreach ($manpowers as $mp) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface')->where('id', $mp->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $quantityFirst = round($mp->workday * $surface, 2);
            $amountFirst = round($mp->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('manpower_items')
                    ->where('man_power_id', $mp->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $mp->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($mp->level1_id, $mp->level1_name, $mp->level2_id, $mp->level2_name, $amount);
        }
        // SERVICES
        $services = \App\Models\Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCenters)
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        foreach ($services as $s) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface')->where('id', $s->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $quantity = (($s->unit_id == 4 && $s->unit_id_price == 3) || ($s->unit_id == 2 && $s->unit_id_price == 1)) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('service_items')
                    ->where('service_id', $s->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $s->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $amount);
        }
        // SUPPLIES
        $supplies = \App\Models\Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCenters)
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        foreach ($supplies as $s) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface')->where('id', $s->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $quantity = (($s->unit_id == 4 && $s->unit_id_price == 3) || ($s->unit_id == 2 && $s->unit_id_price == 1)) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('supply_items')
                    ->where('supply_id', $s->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $s->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $amount);
        }
        return collect(array_values($totals));
    }

        // Calcula los totales mensuales de administración
    private function getMonthsAdministration($team_id = null)
    {
        $season_id = session('season_id');
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $result = array_fill_keys($months, 0);
        $administrations = DB::table('administrations as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        $administrations = $administrations->get();
        foreach ($administrations as $adm) {
            $items = DB::table('administration_items')
                ->where('administration_id', $adm->id)
                ->whereIn('month_id', $months)
                ->get();
            foreach ($items as $item) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity, 2);
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }

    // Calcula los totales mensuales de fields
    private function getMonthsFields($team_id = null)
    {
        $season_id = session('season_id');
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $result = array_fill_keys($months, 0);
        $fields = DB::table('fields as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        $fields = $fields->get();
        foreach ($fields as $adm) {
            $items = DB::table('field_items')
                ->where('field_id', $adm->id)
                ->whereIn('month_id', $months)
                ->get();
            foreach ($items as $item) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity, 2);
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }



}





