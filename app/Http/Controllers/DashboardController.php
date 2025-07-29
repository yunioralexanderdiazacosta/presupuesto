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
use App\Models\Harvest;
use App\Models\ManPower;
use App\Models\Supply;
use App\Models\Service;
use Inertia\Inertia;
//use App\Services\WeatherService;
use Spatie\Permission\Traits\HasRoles;

/**
 * Controlador principal del Dashboard.
 * Calcula y agrupa los datos necesarios para mostrar los gráficos y tablas del dashboard.
 * Incluye funciones auxiliares para obtener totales, agrupaciones y métricas por estado de desarrollo y por hectárea.
 */
class DashboardController extends Controller
{
    use HasRoles;
    use \App\Http\Controllers\Traits\BudgetTotalsTrait;
    public $month_id = '';

    public $totalAgrochemical = 0;
    public $totalFertilizer = 0;
    public $totalManPower = 0;
    public $totalSupplies = 0;
    public $totalServices = 0;
    public $totalHarvests = 0;
    public $monthsAgrochemical = [];
    public $monthsFertilizer = [];
    public $monthsManPower = [];
    public $monthsSupplies = [];
    public $monthsServices = [];
    public $monthsHarvests = [];

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
        $harvests = \App\Models\Harvest::where('season_id', $season_id)
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
            'harvests' => $harvests,
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
        $totalHarvest = (float) $this->getTotalHarvest($season_id, $team_id);

        $labels = [
            'Generales Campo',
            'Administración',
            'Fertilizantes',
            'Mano de Obra',
            'Agroquímicos',
            'Insumos',
            'Servicios',
            'Cosecha',
        ];
        $totals = [
            $totalField,
            $totalAdministration,
            $totalFertilizer,
            $totalManPower,
            $totalAgrochemical,
            $totalSupplies,
            $totalServices,
            $totalHarvest,
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





    //public function __invoke(Request $request, WeatherService $weatherService)
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        //Si es super admin
        if ($user->hasRole('Super Admin')) {
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
            $costCenters = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function ($query) use ($user) {
                $query->where('team_id', $user->team_id);
            })->get()->transform(function ($costCenter) {
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
            $this->getHarvestsProducts($costCentersId);
            $pieLabels = ['Agroquimicos', 'Fertilizantes', 'Mano de obra', 'Servicios', 'Insumos', 'Cosecha'];
            $pieDatasets = [
                [
                    "data" => [round($this->totalAgrochemical), round($this->totalFertilizer), round($this->totalManPower), round($this->totalServices), round($this->totalSupplies), round($this->totalHarvests)],
                    "backgroundColor" => ['#36a2eb', '#ff6384', '#ffce56', '#008000', '#FF2C2C', '#FFA500'],
                    "hoverOffset" => 4,
                    "cutout" => 0
                ]
            ];
            // Calcular totales de administración y fields
            $totalAdministration = $this->getAdministrationTotalsByLevel12($user->team_id)->sum('total_amount');
            $totalFields = $this->getFieldTotalsByLevel12($user->team_id)->sum('total_amount');
            $totalSeason = number_format(($this->totalAgrochemical + $this->totalFertilizer + $this->totalManPower + $this->totalServices + $this->totalSupplies + $this->totalHarvests + $totalAdministration + $totalFields), 0, ',', '.');
            $totalAgrochemical = number_format($this->totalAgrochemical, 0, ',', '.');
            $totalFertilizer = number_format($this->totalFertilizer, 0, ',', '.');
            $totalManPower = number_format($this->totalManPower, 0, ',', '.');
            $totalServices = number_format($this->totalServices, 0, ',', '.');
            $totalSupplies = number_format($this->totalSupplies, 0, ',', '.');
            $totalHarvests = number_format($this->totalHarvests, 0, ',', '.');

            // NUEVO: Calcular y formatear los meses de administración y fields
            $monthsAdministrationRaw = $this->getMonthsAdministration($user->team_id);
            $monthsFieldsRaw = $this->getMonthsFields($user->team_id);
            $monthsAdministration = [];
            foreach ($monthsAdministrationRaw as $key => $value) {
                $monthsAdministration[$key] = number_format($value, 0, ',', '.');
            }
            $monthsFields = [];

            foreach ($monthsFieldsRaw as $key => $value) {
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
            foreach ($this->monthsAgrochemical as $key => $value) {
                $monthsAgrochemical[$key] = number_format($value, 0, ',', '.');
            }
            $monthsFertilizer = [];
            foreach ($this->monthsFertilizer as $key => $value) {
                $monthsFertilizer[$key] = number_format($value, 0, ',', '.');
            }
            $monthsManPower = [];
            foreach ($this->monthsManPower as $key => $value) {
                $monthsManPower[$key] = number_format($value, 0, ',', '.');
            }
            $monthsServices = [];
            foreach ($this->monthsServices as $key => $value) {
                $monthsServices[$key] = number_format($value, 0, ',', '.');
            }
            $monthsSupplies = [];
            foreach ($this->monthsSupplies as $key => $value) {
                $monthsSupplies[$key] = number_format($value, 0, ',', '.');
            }
            $monthsHarvests = [];
            foreach ($this->monthsHarvests as $key => $value) {
                $monthsHarvests[$key] = number_format($value, 0, ',', '.');
            }
            // Weather integration (comentado)
            // $city = $request->input('city') ?? $request->input('weatherCity') ?? 'Curico, Chile'; // Usa la ciudad enviada por el frontend o la default
            // $weather = $weatherService->getCurrentWeather($city);


            

            // Calcular costos prorrateados de administración y fields por especie (fruit)
            $fruits = \App\Models\Fruit::all();
            $costCenters = \App\Models\CostCenter::where('season_id', $season_id)->get(['id', 'fruit_id', 'surface']);
            $totalSurface = $costCenters->sum('surface');
            $totalAdministration = $this->getAdministrationTotalsByLevel12($user->team_id)->sum('total_amount');
            $totalFields = $this->getFieldTotalsByLevel12($user->team_id)->sum('total_amount');
            $totalAdminFields = $totalAdministration + $totalFields;
            // Agrupar superficie por fruit_id
            $surfaceByFruit = $costCenters->groupBy('fruit_id')->map(function($group) {
                return $group->sum('surface');
            });
            $adminFieldsByFruit = [];
            foreach ($fruits as $fruit) {
                $surface = $surfaceByFruit[$fruit->id] ?? 0;
                $prorrateo = ($totalSurface > 0) ? ($surface / $totalSurface) : 0;
                $adminFieldsByFruit[$fruit->id] = [
                    'name' => $fruit->name,
                    'admin_fields_total' => round($totalAdminFields * $prorrateo, 2),
                    'surface' => $surface,
                    'prorrateo' => $prorrateo
                ];
            }

            // Calcular total de cosecha por especie (fruit)
            $totalHarvestByFruit = [];
            foreach ($fruits as $fruit) {
                $totalHarvestByFruit[$fruit->id] = $this->getTotalHarvest($season_id, $user->team_id, $fruit->id);
            }




            // Calcular totales de agroquímicos por estado de desarrollo
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
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($agrochemicalByDevState[$devStateId])) {
                        $agrochemicalByDevState[$devStateId] = 0;
                    }
                    $agrochemicalByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de agroquímicos por estado de desarrollo
            $agrochemicalExpensePerHectare = [];
            foreach ($agrochemicals as $agrochemical) {
                $byDev = $this->getAgrochemicalExpensePerHectareByDevelopmentState($agrochemical, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($agrochemicalExpensePerHectare[$devStateId])) {
                        $agrochemicalExpensePerHectare[$devStateId] = 0;
                    }
                    $agrochemicalExpensePerHectare[$devStateId] += $amount;
                }
            }
            // Calcular totales de fertilizantes por estado de desarrollo
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
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($fertilizerByDevState[$devStateId])) {
                        $fertilizerByDevState[$devStateId] = 0;
                    }
                    $fertilizerByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de fertilizantes por estado de desarrollo
            $fertilizerExpensePerHectare = [];
            foreach ($fertilizers as $fertilizer) {
                $byDev = $this->getFertilizerExpensePerHectareByDevelopmentState($fertilizer, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($fertilizerExpensePerHectare[$devStateId])) {
                        $fertilizerExpensePerHectare[$devStateId] = 0;
                    }
                    $fertilizerExpensePerHectare[$devStateId] += $amount;
                }
            }
            // Calcular totales de mano de obra por estado de desarrollo
            $manPowerByDevState = [];
            $manPowers = ManPower::from('man_powers as mp')
                ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
                ->select('mp.id', 'mp.price', 'mp.workday')
                ->whereIn('mpi.cost_center_id', $costCentersId)
                ->groupBy('mp.id', 'mp.price', 'mp.workday')
                ->get();
            foreach ($manPowers as $manPower) {
                $byDev = $this->getManPowerResultByDevelopmentState($manPower, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($manPowerByDevState[$devStateId])) {
                        $manPowerByDevState[$devStateId] = 0;
                    }
                    $manPowerByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de mano de obra por estado de desarrollo
            $manPowerExpensePerHectare = [];
            foreach ($manPowers as $manPower) {
                $byDev = $this->getManPowerExpensePerHectareByDevelopmentState($manPower, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($manPowerExpensePerHectare[$devStateId])) {
                        $manPowerExpensePerHectare[$devStateId] = 0;
                    }
                    $manPowerExpensePerHectare[$devStateId] += $amount;
                }
            }




            // Calcular totales de servicios por estado de desarrollo
            $servicesByDevState = [];
            $services = Service::from('services as s')
                ->join('service_items as si', 's.id', 'si.service_id')
                ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
                ->whereIn('si.cost_center_id', $costCentersId)
                ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
                ->get();
            foreach ($services as $service) {
                $byDev = $this->getServiceResultByDevelopmentState($service, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($servicesByDevState[$devStateId])) {
                        $servicesByDevState[$devStateId] = 0;
                    }
                    $servicesByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de servicios por estado de desarrollo
            $servicesExpensePerHectare = [];
            foreach ($services as $service) {
                $byDev = $this->getServiceExpensePerHectareByDevelopmentState($service, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($servicesExpensePerHectare[$devStateId])) {
                        $servicesExpensePerHectare[$devStateId] = 0;
                    }
                    $servicesExpensePerHectare[$devStateId] += $amount;
                }
            }






            // Calcular totales de cosecha por estado de desarrollo
            $harvestsByDevState = [];
            $harvests = Harvest::from('harvests as s')
                ->join('harvest_items as si', 's.id', 'si.harvest_id')
                ->select('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
                ->whereIn('si.cost_center_id', $costCentersId)
                ->groupBy('s.id', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price')
                ->get();
            foreach ($harvests as $harvest) {
                $byDev = $this->getHarvestResultByDevelopmentState($harvest, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($harvestsByDevState[$devStateId])) {
                        $harvestsByDevState[$devStateId] = 0;
                    }
                    $harvestsByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de cosecha por estado de desarrollo
            $harvestsExpensePerHectare = [];
            foreach ($harvests as $harvest) {
                $byDev = $this->getHarvestExpensePerHectareByDevelopmentState($harvest, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($harvestsExpensePerHectare[$devStateId])) {
                        $harvestsExpensePerHectare[$devStateId] = 0;
                    }
                    $harvestsExpensePerHectare[$devStateId] += $amount;
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
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($suppliesByDevState[$devStateId])) {
                        $suppliesByDevState[$devStateId] = 0;
                    }
                    $suppliesByDevState[$devStateId] += $amount;
                }
            }
            // Calcular gasto por hectárea de insumos por estado de desarrollo
            $suppliesExpensePerHectare = [];
            foreach ($supplies as $supply) {
                $byDev = $this->getSupplyExpensePerHectareByDevelopmentState($supply, $costCentersId);
                foreach ($byDev as $devStateId => $amount) {
                    if (!isset($suppliesExpensePerHectare[$devStateId])) {
                        $suppliesExpensePerHectare[$devStateId] = 0;
                    }
                    $suppliesExpensePerHectare[$devStateId] += $amount;
                }
            }


            //obtener total estimacion en kilos
            $totalEstimatedKilosData = $this->getTotalEstimatedKilos($season_id, $user->team_id);
            $kilosByFruit = $totalEstimatedKilosData['kilosByFruit'] ?? [];
            $fruitNames = $totalEstimatedKilosData['fruitNames'] ?? [];

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
            // Pasar todos los datos al frontend
            // Nuevo: adminFieldsByFruit contiene el total prorrateado de administración+fields por especie
            return Inertia::render('Dashboard', compact(
                'totalSeason',
                'pieLabels',
                'pieDatasets',
                'monthsAgrochemical',
                'totalAgrochemical',
                'monthsFertilizer',
                'totalFertilizer',
                'monthsManPower',
                'totalManPower',
                'totalServices',
                'monthsServices',
                'totalSupplies',
                'monthsSupplies',
                'totalHarvests',
                'monthsHarvests',
                'monthsAdministration',
                'monthsFields',
                'months',
                //'weather',
                //'city',
                'agrochemicalByDevState',
                'fertilizerByDevState',
                'manPowerByDevState',
                'harvestsByDevState',
                'servicesByDevState',
                'suppliesByDevState',
                'agrochemicalExpensePerHectare',
                'fertilizerExpensePerHectare',
                'manPowerExpensePerHectare',
                'servicesExpensePerHectare',
                'harvestsExpensePerHectare',
                'suppliesExpensePerHectare',
                'devStates',
                'administrationTotalsByLevel12',
                'fieldTotalsByLevel12',
                'totalsByLevel12',
                'entityCounts',
                'totalSurface',
                'mainTotalsAndPercents', // <-- nuevo prop para los gauges
                'totalEstimatedKilosData', // <-- nuevo prop para total estimado en kilos
                'kilosByFruit',
                'fruitNames',
                'adminFieldsByFruit',
                'totalHarvestByFruit'
            ));
        }
    }

    /**
     * Obtiene y acumula los totales de agroquímicos por cost center y por mes.
     * Actualiza las propiedades $this->totalAgrochemical y $this->monthsAgrochemical.
     * No retorna datos útiles, solo realiza side-effects.
     */
    private function getAgrochemicalProducts($costCentersId)
    {
        // Obtener todos los productos agroquímicos relevantes
        $products = Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->whereIn('ai.cost_center_id', $costCentersId)
            ->groupBy('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de agrochemical_items de una sola vez
        $itemCounts = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('agrochemical_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->agrochemical_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalAgrochemical = 0;
        $this->monthsAgrochemical = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
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
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsAgrochemical[$month])) {
                        $this->monthsAgrochemical[$month] = 0;
                    }
                    $this->monthsAgrochemical[$month] += $amountMonth;
                    $this->totalAgrochemical += $amountMonth;
                }
            }
        }

        return $products;
    }

    /**
     * Obtiene y acumula los totales de fertilizantes por cost center y por mes.
     * Actualiza las propiedades $this->totalFertilizer y $this->monthsFertilizer.
     */
    private function getFertilizerProducts($costCentersId)
    {
        // Obtener todos los productos fertilizantes relevantes
        $products = Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->whereIn('fi.cost_center_id', $costCentersId)
            ->groupBy('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de fertilizer_items de una sola vez
        $itemCounts = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('fertilizer_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->fertilizer_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalFertilizer = 0;
        $this->monthsFertilizer = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                foreach ($months as $month) {
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsFertilizer[$month])) {
                        $this->monthsFertilizer[$month] = 0;
                    }
                    $this->monthsFertilizer[$month] += $amountMonth;
                    $this->totalFertilizer += $amountMonth;
                }
            }
        }

        return $products;
    }

    /**
     * Obtiene y acumula los totales de mano de obra por cost center y por mes.
     * Actualiza las propiedades $this->totalManPower y $this->monthsManPower.
     */
    private function getManPowerProducts($costCentersId)
    {
        // Obtener todos los productos de mano de obra relevantes
        $products = ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->leftJoin('units as u', 'mp.unit_id', 'u.id')
            ->select('mp.id', 'mp.price', 'mp.workday')
            ->whereIn('mpi.cost_center_id', $costCentersId)
            ->groupBy('mp.id', 'mp.price', 'mp.workday')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de manpower_items de una sola vez
        $itemCounts = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('man_power_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->man_power_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalManPower = 0;
        $this->monthsManPower = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                foreach ($months as $month) {
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsManPower[$month])) {
                        $this->monthsManPower[$month] = 0;
                    }
                    $this->monthsManPower[$month] += $amountMonth;
                    $this->totalManPower += $amountMonth;
                }
            }
        }

        return $products;
    }

    /**
     * Calcula el monto total de mano de obra en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    private function getManPowerResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach ($costCentersId as $costCenter) {
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
            foreach ($data as $month) {
                $count = DB::table('manpower_items')
                    ->select('man_power_id')
                    ->where('man_power_id', $value->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $costCenter)
                    ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;
                if (!isset($this->monthsManPower[$month])) {
                    $this->monthsManPower[$month] = 0;
                }
                $this->monthsManPower[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalManPower += $totalAmount;
    }


    /**
     * Obtiene y acumula los totales de servicios por cost center y por mes.
     * Actualiza las propiedades $this->totalServices y $this->monthsServices.
     */
    private function getServicesProducts($costCentersId)
    {
        // Obtener todos los productos de servicios relevantes
        $products = Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }




        // Obtener todos los conteos de service_items de una sola vez
        $itemCounts = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('service_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->service_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalServices = 0;
        $this->monthsServices = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                foreach ($months as $month) {
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsServices[$month])) {
                        $this->monthsServices[$month] = 0;
                    }
                    $this->monthsServices[$month] += $amountMonth;
                    $this->totalServices += $amountMonth;
                }
            }
        }

        return $products;
    }

    private function getHarvestsProducts($costCentersId)
    {
        // Obtener todos los productos de servicios relevantes
        $products = Harvest::from('harvests as s')
            ->join('harvest_items as si', 's.id', 'si.harvest_id')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }




        // Obtener todos los conteos de harvest_items de una sola vez
        $itemCounts = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('harvest_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->harvest_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalHarvests = 0;
        $this->monthsHarvests = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                foreach ($months as $month) {
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsHarvests[$month])) {
                        $this->monthsHarvests[$month] = 0;
                    }
                    $this->monthsHarvests[$month] += $amountMonth;
                    $this->totalHarvests += $amountMonth;
                }
            }
        }

        return $products;
    }





    /**
     * Calcula el monto total de un servicio en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    private function getServicesResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach ($costCentersId as $costCenter) {
            $first = CostCenter::select('surface')->where('id', $costCenter)->first();

            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

            $surface = $first->surface;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach ($data as $month) {
                $count = DB::table('service_items')
                    ->select('service_id')
                    ->where('service_id', $value->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $costCenter)
                    ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;


                if (!isset($this->monthsServices[$month])) {
                    $this->monthsServices[$month] = 0;
                }
                $this->monthsServices[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalServices += $totalAmount;
    }


    private function getHarvestsResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach ($costCentersId as $costCenter) {
            $first = CostCenter::select('surface')->where('id', $costCenter)->first();

            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

            $surface = $first->surface;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach ($data as $month) {
                $count = DB::table('harvest_items')
                    ->select('harvest_id')
                    ->where('harvest_id', $value->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $costCenter)
                    ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;


                if (!isset($this->monthsHarvests[$month])) {
                    $this->monthsHarvests[$month] = 0;
                }
                $this->monthsHarvests[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
        }

        $this->totalHarvests += $totalAmount;
    }



    /**
     * Obtiene y acumula los totales de insumos por cost center y por mes.
     * Actualiza las propiedades $this->totalSupplies y $this->monthsSupplies.
     */
    private function getSuppliesProducts($costCentersId)
    {
        // Obtener todos los productos de insumos relevantes
        $products = Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->whereIn('si.cost_center_id', $costCentersId)
            ->groupBy('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->get();

        // Obtener superficies de todos los cost centers de una sola vez
        $costCenters = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        // Obtener todos los meses del ciclo
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de supply_items de una sola vez
        $itemCounts = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->whereIn('supply_id', $products->pluck('id'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->supply_id . '-' . $row->cost_center_id . '-' . $row->month_id;
            });

        $this->totalSupplies = 0;
        $this->monthsSupplies = [];

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($costCenters[$costCenter]) ? $costCenters[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);

                foreach ($months as $month) {
                    $key = $value->id . '-' . $costCenter . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($this->monthsSupplies[$month])) {
                        $this->monthsSupplies[$month] = 0;
                    }
                    $this->monthsSupplies[$month] += $amountMonth;
                    $this->totalSupplies += $amountMonth;
                }
            }
        }

        return $products;
    }

    /**
     * Calcula el monto total de un insumo en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    private function getSuppliesResult($value, $costCentersId)
    {
        $totalAmount = 0;
        $currentMonth = $this->month_id;
        foreach ($costCentersId as $costCenter) {
            $first = CostCenter::select('surface')->where('id', $costCenter)->first();

            $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

            $surface = $first->surface;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);

            $data = array();

            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $id = date('n', mktime(0, 0, 0, $x, 1));
                array_push($data, $id);
            }

            $totalAmountCostCenter = 0;
            foreach ($data as $month) {
                $count = DB::table('supply_items')
                    ->select('supply_id')
                    ->where('supply_id', $value->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $costCenter)
                    ->count();

                $amountMonth = $count > 0 ? $amountFirst : 0;
                $totalAmountCostCenter += $amountMonth;

                if (!isset($this->monthsSupplies[$month])) {
                    $this->monthsSupplies[$month] = 0;
                }
                $this->monthsSupplies[$month] += $amountMonth;
            }
            $totalAmount += $totalAmountCostCenter;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de agrochemical_items de una sola vez
        $itemCounts = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('agrochemical_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $surface = $center->surface;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
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
        // Obtener todos los cost centers con su development_state_id
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();
        foreach ($costCenters->groupBy('development_state_id') as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $surface = $center->surface;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);
                $data = [];
                for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                    $id = date('n', mktime(0, 0, 0, $x, 1));
                    array_push($data, $id);
                }
                foreach ($data as $month) {
                    $count = DB::table('agrochemical_items')
                        ->select('agrochemical_id')
                        ->where('agrochemical_id', $value->id)
                        ->where('month_id', $month)
                        ->where('cost_center_id', $center->id)
                        ->count();
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            // Evitar división por cero
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de fertilizer_items de una sola vez
        $itemCounts = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('fertilizer_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de fertilizer_items de una sola vez
        $itemCounts = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('fertilizer_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de manpower_items de una sola vez
        $itemCounts = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('man_power_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de manpower_items de una sola vez
        $itemCounts = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('man_power_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de service_items de una sola vez
        $itemCounts = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('service_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
        }
        return $result;
    }







    private function getHarvestResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de harvest_items de una sola vez
        $itemCounts = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('harvest_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de service_items de una sola vez
        $itemCounts = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('service_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
        }
        return $result;
    }


    private function getHarvestExpensePerHectareByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $currentMonth = $this->month_id;
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de harvest_items de una sola vez
        $itemCounts = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('harvest_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de supply_items de una sola vez
        $itemCounts = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('supply_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
            }
            $result[$devStateId] = $totalAmountDev;
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
        // Obtener todos los cost centers con su development_state_id y surface
        $costCenters = \App\Models\CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'development_state_id', 'surface')
            ->get();

        // Mapear cost_center_id a development_state_id y surface
        $costCenterDevMap = [];
        foreach ($costCenters as $center) {
            $costCenterDevMap[$center->id] = [
                'dev' => $center->development_state_id,
                'surface' => $center->surface
            ];
        }

        // Obtener todos los meses del ciclo
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        // Obtener todos los conteos de supply_items de una sola vez
        $itemCounts = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as total'))
            ->where('supply_id', $value->id)
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get()
            ->groupBy(function ($row) {
                return $row->cost_center_id . '-' . $row->month_id;
            });

        // Calcular totales por estado de desarrollo
        $groupedCenters = $costCenters->groupBy('development_state_id');
        foreach ($groupedCenters as $devStateId => $centers) {
            $totalAmountDev = 0;
            $totalSurface = 0;
            foreach ($centers as $center) {
                $surface = $center->surface;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $key = $center->id . '-' . $month;
                    $count = isset($itemCounts[$key]) && $itemCounts[$key][0]->total > 0 ? $itemCounts[$key][0]->total : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    $totalAmountDev += $amountMonth;
                }
                $totalSurface += $surface;
            }
            $result[$devStateId] = $totalSurface > 0 ? $totalAmountDev / $totalSurface : 0;
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

        $administrations = DB::table('administrations as a')
            ->join('level3s as l3', 'a.subfamily_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->select(
                'l1.id as level1_id',
                'l1.name as level1_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'a.id as administration_id',
                'a.price',
                'a.quantity',
                'a.unit_id'
            )
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        $administrations = $administrations->get();

        $totals = [];
        foreach ($administrations as $adm) {
            // Buscar los meses activos en los que aparece este administration_id
            $activeMonths = DB::table('administration_items')
                ->where('administration_id', $adm->administration_id)
                ->whereIn('month_id', $months)
                ->distinct('month_id')
                ->pluck('month_id');
            $countMonths = $activeMonths->count();
            if ($countMonths > 0) {
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
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2, 4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity * $countMonths, 2);
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
                'l1.id as level1_id',
                'l1.name as level1_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'a.id as field_id',
                'a.price',
                'a.quantity',
                'a.unit_id'
            )
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        $fields = $fields->get()->keyBy('field_id');

        // Obtener todos los meses activos de field_items de una sola vez
        $fieldMonthCounts = DB::table('field_items')
            ->whereIn('field_id', $fields->keys())
            ->whereIn('month_id', $months)
            ->select('field_id', DB::raw('COUNT(DISTINCT month_id) as count_months'))
            ->groupBy('field_id')
            ->pluck('count_months', 'field_id');

        $totals = [];
        foreach ($fields as $adm) {
            $countMonths = isset($fieldMonthCounts[$adm->field_id]) ? $fieldMonthCounts[$adm->field_id] : 0;
            if ($countMonths > 0) {
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
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2, 4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity * $countMonths, 2);
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
        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($team_id) {
            $costCentersQuery->whereHas('season.team', function ($query) use ($team_id) {
                $query->where('team_id', $team_id);
            });
        }
        $costCenters = $costCentersQuery->get(['id', 'fruit_id'])->keyBy('id');
        // Pre-cargar los nombres de fruta
        $fruitIds = $costCenters->pluck('fruit_id')->unique()->filter();
        $fruits = $fruitIds->count() ? \App\Models\Fruit::whereIn('id', $fruitIds)->pluck('name', 'id') : collect();
        $totals = [];
        $addTotal = function ($level1_id, $level1_name, $level2_id, $level2_name, $fruit_id, $fruit_name, $amount) use (&$totals) {
            $key = $level1_id . '-' . $level2_id . '-' . $fruit_id;
            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'level1_id' => $level1_id,
                    'level1_name' => $level1_name,
                    'level2_id' => $level2_id,
                    'level2_name' => $level2_name,
                    'fruit_id' => $fruit_id,
                    'fruit_name' => $fruit_name,
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
            ->whereIn('ai.cost_center_id', $costCenters->keys())
            ->groupBy('a.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'ai.cost_center_id')
            ->get();
        foreach ($agrochemicals as $a) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $a->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
            $dose = (($a->unit_id == 4 && $a->unit_id_price == 3) || ($a->unit_id == 2 && $a->unit_id_price == 1)) ? ($a->dose / 1000) : $a->dose;
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
            $addTotal($a->level1_id, $a->level1_name, $a->level2_id, $a->level2_name, $fruit_id, $fruit_name, $amount);
        }
        // FERTILIZERS
        $fertilizers = \App\Models\Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('level3s as l3', 'f.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('f.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'fi.cost_center_id')
            ->whereIn('fi.cost_center_id', $costCenters->keys())
            ->groupBy('f.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'fi.cost_center_id')
            ->get();
        foreach ($fertilizers as $f) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $f->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
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
            $addTotal($f->level1_id, $f->level1_name, $f->level2_id, $f->level2_name, $fruit_id, $fruit_name, $amount);
        }
        // MANPOWER
        $manpowers = \App\Models\ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->join('level3s as l3', 'mp.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('mp.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'mpi.cost_center_id')
            ->whereIn('mpi.cost_center_id', $costCenters->keys())
            ->groupBy('mp.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'mpi.cost_center_id')
            ->get();
        foreach ($manpowers as $mp) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $mp->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
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
            $addTotal($mp->level1_id, $mp->level1_name, $mp->level2_id, $mp->level2_name, $fruit_id, $fruit_name, $amount);
        }
        // SERVICES
        $services = \App\Models\Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCenters->keys())
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        foreach ($services as $s) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $s->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
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
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $fruit_id, $fruit_name, $amount);
        }



        // HARVESTS
        $harvests = \App\Models\Harvest::from('harvests as h')
            ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
            ->join('level3s as l3', 'h.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('h.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'hi.cost_center_id')
            ->whereIn('hi.cost_center_id', $costCenters->keys())
            ->groupBy('h.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'hi.cost_center_id')
            ->get();
        foreach ($harvests as $h) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $h->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
            $quantity = (($h->unit_id == 4 && $h->unit_id_price == 3) || ($h->unit_id == 2 && $h->unit_id_price == 1)) ? ($h->quantity / 1000) : $h->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($h->price * $quantityFirst, 2);
            foreach ($months as $month) {
                $count = DB::table('harvest_items')
                    ->where('harvest_id', $h->id)
                    ->where('month_id', $month)
                    ->where('cost_center_id', $h->cost_center_id)
                    ->count();
                $amount += ($count > 0 ? $amountFirst : 0);
            }
            $addTotal($h->level1_id, $h->level1_name, $h->level2_id, $h->level2_name, $fruit_id, $fruit_name, $amount);
        }




        // SUPPLIES
        $supplies = \App\Models\Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCenters->keys())
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        foreach ($supplies as $s) {
            $amount = 0;
            $first = \App\Models\CostCenter::select('surface', 'fruit_id')->where('id', $s->cost_center_id)->first();
            $surface = $first ? $first->surface : 0;
            $fruit_id = $first ? $first->fruit_id : null;
            $fruit_name = $fruit_id && isset($fruits[$fruit_id]) ? $fruits[$fruit_id] : null;
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
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $fruit_id, $fruit_name, $amount);
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
        // Obtener todas las administraciones relevantes
        $administrations = DB::table('administrations as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        $administrations = $administrations->get()->keyBy('id');

        // Obtener todos los items de administración de una sola vez
        $items = DB::table('administration_items')
            ->whereIn('administration_id', $administrations->keys())
            ->whereIn('month_id', $months)
            ->select('administration_id', 'month_id')
            ->get();

        // Precalcular los montos por administración
        $admAmounts = [];
        foreach ($administrations as $adm) {
            $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2, 4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
            $admAmounts[$adm->id] = round($adm->price * $quantity, 2);
        }

        // Sumar los montos por mes
        foreach ($items as $item) {
            if (isset($result[$item->month_id]) && isset($admAmounts[$item->administration_id])) {
                $result[$item->month_id] += $admAmounts[$item->administration_id];
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
        // Obtener todos los fields relevantes
        $fields = DB::table('fields as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        $fields = $fields->get()->keyBy('id');

        // Obtener todos los items de fields de una sola vez
        $items = DB::table('field_items')
            ->whereIn('field_id', $fields->keys())
            ->whereIn('month_id', $months)
            ->select('field_id', 'month_id')
            ->get();

        // Precalcular los montos por field
        $fieldAmounts = [];
        foreach ($fields as $adm) {
            $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2, 4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
            $fieldAmounts[$adm->id] = round($adm->price * $quantity, 2);
        }

        // Sumar los montos por mes
        foreach ($items as $item) {
            if (isset($result[$item->month_id]) && isset($fieldAmounts[$item->field_id])) {
                $result[$item->month_id] += $fieldAmounts[$item->field_id];
            }
        }
        return $result;
    }
}
