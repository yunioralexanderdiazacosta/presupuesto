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
use App\Models\Harvest;
use Inertia\Inertia;
use App\Models\Fruit;
use App\Models\Branch;
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
    
    public $totalHarvests = 0;
    public $totalServices = 0;

    public $monthsAgrochemical = [];

    public $monthsFertilizer = [];

    public $monthsManPower = [];

    public $monthsSupplies = [];

    public $monthsServices = [];

    public $monthsHarvests = [];

    // Cached data to avoid redundant queries
    private $cachedCostCenters = null;
    private $cachedSurfaces = null;
    private $cachedCenterData = null;
    private $cachedSurfaceData = null;
    private $cachedMonths = null;
    private $agrochemicalItemIndex = null;
    private $fertilizerItemIndex = null;
    private $manPowerItemIndex = null;
    private $serviceItemIndex = null;
    private $harvestItemIndex = null;
    private $supplyItemIndex = null;

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
        $b = [$season_id, $team_id];
        $counts = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM agrochemicals WHERE season_id = ? AND team_id = ?) as agrochemicals,
                (SELECT COUNT(*) FROM fertilizers WHERE season_id = ? AND team_id = ?) as fertilizers,
                (SELECT COUNT(*) FROM man_powers WHERE season_id = ? AND team_id = ?) as manpowers,
                (SELECT COUNT(*) FROM supplies WHERE season_id = ? AND team_id = ?) as supplies,
                (SELECT COUNT(*) FROM services WHERE season_id = ? AND team_id = ?) as services,
                (SELECT COUNT(*) FROM harvests WHERE season_id = ? AND team_id = ?) as harvests,
                (SELECT COUNT(*) FROM fields WHERE season_id = ? AND team_id = ?) as fields,
                (SELECT COUNT(*) FROM administrations WHERE season_id = ? AND team_id = ?) as administrations
        ", array_merge($b, $b, $b, $b, $b, $b, $b, $b));
        return (array) $counts;
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
        $totalHarvests = (float) $this->getTotalHarvest($season_id, $team_id);

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
            $totalHarvests
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

        // Filtro de sucursal (opcional), controla toda la vista igual que en el Dashboard
        $selectedBranchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;

        // Sucursales disponibles para el select del frontend
        $branches = Branch::where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name])
            ->values();

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
        $costCentersQuery = CostCenter::select('id', 'name')->where('season_id', $season_id)->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        });
        if ($selectedBranchId) {
            $costCentersQuery->where('branch_id', $selectedBranchId);
        }
        $costCenters = $costCentersQuery->get()->transform(function($costCenter){
            return [
                'label' => $costCenter->name,
                'value' => $costCenter->id
            ];
        });
        $costCentersId = $costCenters->pluck('value');

        // Cache months array (used by all product methods)
        $this->cachedMonths = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $this->cachedMonths[] = date('n', mktime(0, 0, 0, $x, 1));
        }

        // Cache CostCenter data (eliminates 20+ redundant queries)
        $this->cachedCostCenters = CostCenter::whereIn('id', $costCentersId)
            ->select('id', 'fruit_id', 'development_state_id', 'surface')
            ->get();
        $this->cachedSurfaces = $this->cachedCostCenters->pluck('surface', 'id');
        $this->cachedCenterData = [];
        foreach ($this->cachedCostCenters as $c) {
            $this->cachedCenterData[$c->id] = [
                'fruit_id' => $c->fruit_id,
                'development_state_id' => $c->development_state_id,
                'surface' => $c->surface
            ];
        }
        // Cache surface data grouped by fruit_id and development_state_id (used 6 times for ExpensePerHectare)
        $this->cachedSurfaceData = $this->cachedCostCenters->groupBy('fruit_id')
            ->map(function ($group) {
                return $group->groupBy('development_state_id')
                    ->map(function ($g) { return $g->sum('surface'); });
            })
            ->toArray();

        // Get products and save itemIndex for ByDevState reuse
        $agrochemicalProducts = $this->getAgrochemicalProducts($costCentersId);
        $fertilizerProducts = $this->getFertilizerProducts($costCentersId);
        $manPowerProducts = $this->getManPowerProducts($costCentersId);
        $serviceProducts = $this->getServicesProducts($costCentersId);
        $supplyProducts = $this->getSuppliesProducts($costCentersId);
        $harvestProducts = $this->getHarvestsProducts($costCentersId);
        $pieLabels = ['Agroquimicos', 'Fertilizantes', 'Mano de obra', 'Servicios', 'Insumos', 'Cosecha'];
        $pieDatasets = [
            [
                "data" => [round($this->totalAgrochemical), round($this->totalFertilizer), round($this->totalManPower), round($this->totalServices), round($this->totalSupplies), round($this->totalHarvests)],
                "backgroundColor" => ['#36a2eb', '#ff6384', '#ffce56', '#008000', '#FF2C2C', '#FFA500'],
                "hoverOffset" => 4,
                "cutout" => 0
            ]
        ];
        // Calcular totales de administración y fields (filtrados por sucursal si corresponde)
        $administrationTotalsByLevel12 = $this->getAdministrationTotalsByLevel12($user->team_id, $selectedBranchId);
        $fieldTotalsByLevel12 = $this->getFieldTotalsByLevel12($user->team_id, $selectedBranchId);
        $totalAdministration = $administrationTotalsByLevel12->sum('total_amount');
        $totalFields = $fieldTotalsByLevel12->sum('total_amount');
        $totalSeason = number_format(($this->totalAgrochemical + $this->totalFertilizer + $this->totalManPower + $this->totalServices + $this->totalSupplies + $totalAdministration + $totalFields), 0, ',', '.');
        // Enviar los totales como números puros para el frontend (para Totales Mensuales)
        $totalAgrochemical = $this->totalAgrochemical;
        $totalFertilizer = $this->totalFertilizer;
        $totalManPower = $this->totalManPower;
        $totalServices = $this->totalServices;
        $totalSupplies = $this->totalSupplies;
        $totalHarvests = $this->totalHarvests;

        // NUEVO: Calcular y formatear los meses de administración y fields (filtrados por sucursal si corresponde)
        $monthsAdministrationRaw = $this->getMonthsAdministration($user->team_id, $selectedBranchId);
        $monthsFieldsRaw = $this->getMonthsFields($user->team_id, $selectedBranchId);
        // Inversiones: obtener totales mensuales y total general (filtradas por sucursal si corresponde)
        $monthsInvestmentsRaw = $this->getInvestmentsTotalByMonth($season_id, $user->team_id, $selectedBranchId);
        $monthsInvestments = [];
        foreach($monthsInvestmentsRaw as $key => $value){
            $monthsInvestments[$key] = (float)$value;
        }
        // Normalizar a 12 meses (1-12)
        $allMonthsInvestments = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsInvestments[$key] = isset($monthsInvestments[$key]) ? $monthsInvestments[$key] : 0;
        }
        $monthsInvestments = $allMonthsInvestments;
        $totalInvestments = array_sum($monthsInvestments);
        $monthsAdministration = [];
        foreach($monthsAdministrationRaw as $key => $value){
            $monthsAdministration[$key] = (float)$value;
        }
        $monthsFields = [];

        foreach($monthsFieldsRaw as $key => $value){
            $monthsFields[$key] = (float)$value;
        }

        // Asegurar que monthsAdministration y monthsFields tengan SIEMPRE los 12 meses (1-12) como claves
        $allMonthsAdministration = [];
        $allMonthsFields = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsAdministration[$key] = isset($monthsAdministration[$key]) ? $monthsAdministration[$key] : 0;
            $allMonthsFields[$key] = isset($monthsFields[$key]) ? $monthsFields[$key] : 0;
        }
        $monthsAdministration = $allMonthsAdministration;
        $monthsFields = $allMonthsFields;

        // Normalizar todos los arrays de meses para que tengan SIEMPRE los 12 meses (1-12) como claves
        $monthsAgrochemical = [];
        foreach($this->monthsAgrochemical as $key => $value){
            $monthsAgrochemical[$key] = (float)$value;
        }
        $allMonthsAgrochemical = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsAgrochemical[$key] = isset($monthsAgrochemical[$key]) ? $monthsAgrochemical[$key] : 0;
        }
        $monthsAgrochemical = $allMonthsAgrochemical;

        $monthsFertilizer = [];
        foreach($this->monthsFertilizer as $key => $value){
            $monthsFertilizer[$key] = (float)$value;
        }
        $allMonthsFertilizer = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsFertilizer[$key] = isset($monthsFertilizer[$key]) ? $monthsFertilizer[$key] : 0;
        }
        $monthsFertilizer = $allMonthsFertilizer;

        $monthsManPower = [];
        foreach($this->monthsManPower as $key => $value){
            $monthsManPower[$key] = (float)$value;
        }
        $allMonthsManPower = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsManPower[$key] = isset($monthsManPower[$key]) ? $monthsManPower[$key] : 0;
        }
        $monthsManPower = $allMonthsManPower;

        $monthsServices = [];
        foreach($this->monthsServices as $key => $value){
            $monthsServices[$key] = (float)$value;
        }
        $allMonthsServices = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsServices[$key] = isset($monthsServices[$key]) ? $monthsServices[$key] : 0;
        }
        $monthsServices = $allMonthsServices;

        $monthsSupplies = [];
        foreach($this->monthsSupplies as $key => $value){
            $monthsSupplies[$key] = (float)$value;
        }
        $allMonthsSupplies = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsSupplies[$key] = isset($monthsSupplies[$key]) ? $monthsSupplies[$key] : 0;
        }
        $monthsSupplies = $allMonthsSupplies;

        $monthsHarvests = [];
        foreach($this->monthsHarvests as $key => $value){
            $monthsHarvests[$key] = (float)$value;
        }
        $allMonthsHarvests = [];
        for ($i = 1; $i <= 12; $i++) {
            $key = (string)$i;
            $allMonthsHarvests[$key] = isset($monthsHarvests[$key]) ? $monthsHarvests[$key] : 0;
        }
        $monthsHarvests = $allMonthsHarvests;
        // Weather integration
        $city = $request->input('city') ?? $request->input('weatherCity') ?? 'Curico, Chile'; // Usa la ciudad enviada por el frontend o la default
        $weather = $weatherService->getCurrentWeather($city);
        
        // Calcular totales por estado de desarrollo usando productos ya cargados y datos cacheados
        // (eliminadas 6 re-queries de productos y 6 queries de surfaceData)
        $agrochemicalByDevState = $this->aggregateByDevState($agrochemicalProducts, 'getAgrochemicalResultByDevelopmentState', $costCentersId);
        $agrochemicalExpensePerHectare = $this->calculateExpensePerHectare($agrochemicalByDevState);

        $fertilizerByDevState = $this->aggregateByDevState($fertilizerProducts, 'getFertilizerResultByDevelopmentState', $costCentersId);
        $fertilizerExpensePerHectare = $this->calculateExpensePerHectare($fertilizerByDevState);

        $manPowerByDevState = $this->aggregateByDevState($manPowerProducts, 'getManPowerResultByDevelopmentState', $costCentersId);
        $manPowerExpensePerHectare = $this->calculateExpensePerHectare($manPowerByDevState);

        $servicesByDevState = $this->aggregateByDevState($serviceProducts, 'getServiceResultByDevelopmentState', $costCentersId);
        $servicesExpensePerHectare = $this->calculateExpensePerHectare($servicesByDevState);

        $harvestsByDevState = $this->aggregateByDevState($harvestProducts, 'getHarvestResultByDevelopmentState', $costCentersId);
        $harvestsExpensePerHectare = $this->calculateExpensePerHectare($harvestsByDevState);

        $suppliesByDevState = $this->aggregateByDevState($supplyProducts, 'getSupplyResultByDevelopmentState', $costCentersId);
        $suppliesExpensePerHectare = $this->calculateExpensePerHectare($suppliesByDevState);

        // Obtener nombres de estados de desarrollo
        $devStates = \App\Models\DevelopmentState::all(['id', 'name'])->keyBy('id')->toArray();

        // administrationTotalsByLevel12 y fieldTotalsByLevel12 ya se calcularon arriba (filtrados por sucursal)
        $totalsByLevel12 = $this->getTotalsByLevel12($user->team_id);

        // Calcular el total de superficie usando datos cacheados
        $totalSurface = $this->cachedCostCenters->sum('surface');
        $entityCounts = self::getEntityCounts($season_id, $user->team_id);
        // Calcular los totales y porcentajes de cada rubro principal, usando los mismos totales ya filtrados por sucursal
        $mainTotalsRaw = [
            'Generales Campo' => (float) $totalFields,
            'Administración'  => (float) $totalAdministration,
            'Fertilizantes'   => (float) $this->totalFertilizer,
            'Mano de Obra'    => (float) $this->totalManPower,
            'Agroquímicos'    => (float) $this->totalAgrochemical,
            'Insumos'         => (float) $this->totalSupplies,
            'Servicios'       => (float) $this->totalServices,
            'Cosecha'         => (float) $this->totalHarvests,
        ];
        $grandTotalMain = array_sum($mainTotalsRaw);
        $mainTotalsAndPercents = array_map(
            fn($label, $total) => [
                'label'   => $label,
                'total'   => $total,
                'percent' => $grandTotalMain > 0 ? round(($total / $grandTotalMain) * 100, 2) : 0,
            ],
            array_keys($mainTotalsRaw),
            array_values($mainTotalsRaw)
        );
        // Construir fruitsMap y pasarlo al frontend
        $fruitsMap = $this->getFruitsMap($user->team_id);
        return Inertia::render('TechnicalPanel', compact(
            'totalSeason', 'pieLabels', 'pieDatasets',
            'monthsAgrochemical', 'totalAgrochemical',
            'monthsFertilizer', 'totalFertilizer',
            'monthsManPower', 'totalManPower',
            'totalServices', 'monthsServices',
            'totalHarvests', 'monthsHarvests',
            'totalSupplies', 'monthsSupplies',
            'monthsAdministration', 'monthsFields',
            'monthsInvestments', 'totalInvestments',
            'months', 'weather', 'city',
            'agrochemicalByDevState',
            'fertilizerByDevState',
            'manPowerByDevState',
            'servicesByDevState',
            'harvestsByDevState',
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
            'fruitsMap',
            'branches',
            'selectedBranchId'
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
     * Agrega resultados ByDevState de múltiples productos usando un método de cálculo específico.
     * Elimina duplicación de código en __invoke.
     */
    private function aggregateByDevState($products, $method, $costCentersId)
    {
        $byDevState = [];
        foreach ($products as $product) {
            $byDev = $this->$method($product, $costCentersId);
            foreach ($byDev as $fruitId => $devStates) {
                foreach ($devStates as $devStateId => $amount) {
                    $fruitIdStr = (string)$fruitId;
                    $devStateIdStr = (string)$devStateId;
                    if (!isset($byDevState[$fruitIdStr][$devStateIdStr])) {
                        $byDevState[$fruitIdStr][$devStateIdStr] = 0;
                    }
                    $byDevState[$fruitIdStr][$devStateIdStr] += $amount;
                }
            }
        }
        return $byDevState;
    }

    /**
     * Calcula gasto por hectárea a partir de datos ByDevState usando surfaceData cacheado.
     */
    private function calculateExpensePerHectare($byDevState)
    {
        $result = [];
        foreach ($byDevState as $fruitIdStr => $devStates) {
            foreach ($devStates as $devStateIdStr => $amount) {
                $surface = $this->cachedSurfaceData[$fruitIdStr][$devStateIdStr] ?? 0;
                $result[$fruitIdStr][$devStateIdStr] = $surface > 0 ? $amount / $surface : 0;
            }
        }
        return $result;
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

        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por agrochemical_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->agrochemical_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->agrochemicalItemIndex = $itemIndex;

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

        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por fertilizer_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->fertilizer_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->fertilizerItemIndex = $itemIndex;

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

        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por man_power_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->man_power_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->manPowerItemIndex = $itemIndex;

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

        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por service_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->service_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->serviceItemIndex = $itemIndex;

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

    private function getHarvestsProducts($costCentersId)
    {
        $currentMonth = $this->month_id;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        // Consulta agregada: suma de items por harvest_id, cost_center_id, month_id
        $items = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id', 'month_id')
            ->get();

        // Traer todos los productos necesarios de una vez
        $products = Harvest::from('harvests as h')
            ->leftJoin('units as u', 'h.unit_id_price', 'u.id')
            ->select('h.id', 'h.product_name', 'h.price', 'h.quantity', 'h.unit_id', 'h.unit_id_price',  'u.name')
            ->whereIn('h.id', $items->pluck('harvest_id')->unique())
            ->get();

     
        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por harvest_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->harvest_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->harvestItemIndex = $itemIndex;

        // Calcular totales y meses
        foreach ($products as $value) {
            $this->getHarvestsResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces);
        }
      
        return $products;
    }

    /**
     * Calcula el monto total de un servicio en todos los cost centers y meses.
     * Actualiza los acumuladores globales.
     */
    // Versión optimizada: calcula totales usando los datos agregados
    private function getHarvestsResultOptimized($value, $costCentersId, $months, $itemIndex, $surfaces)
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
                if (!isset($this->monthsHarvests[$month])) {
                    $this->monthsHarvests[$month] = 0;
                }
                $this->monthsHarvests[$month] += $amountMonth;
            }
        }
        $this->totalHarvests += $totalAmount;

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

        // Usar superficies cacheadas
        $surfaces = $this->cachedSurfaces;

        // Indexar items por supply_id y cost_center_id y month_id
        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->supply_id][$item->cost_center_id][$item->month_id] = $item->count;
        }
        $this->supplyItemIndex = $itemIndex;

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
     * Usa datos cacheados (cachedCenterData + agrochemicalItemIndex) - sin queries.
     */
    private function getAgrochemicalResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;

        foreach ($this->agrochemicalItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            if ($value->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($value->dose_type_id == 2) {
                $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
        }
        return $result;
    }

    /**
     * Calcula el total de fertilizantes por estado de desarrollo.
     * Usa datos cacheados (cachedCenterData + fertilizerItemIndex) - sin queries.
     */
    private function getFertilizerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;

        foreach ($this->fertilizerItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
        }
        return $result;
    }

    /**
     * Calcula el total de mano de obra separado por development_state.
     * Usa datos cacheados - sin queries.
     */
    private function getManPowerResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        foreach ($this->manPowerItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            $quantityFirst = round($value->workday * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
        }
        return $result;
    }

    /**
     * Calcula el total de servicios separado por development_state.
     * Usa datos cacheados - sin queries.
     */
    private function getServiceResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

        foreach ($this->serviceItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
        }
        return $result;
    }

   /**
    * Calcula el total de cosecha separado por development_state.
    * Usa datos cacheados - sin queries.
    */
   private function getHarvestResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

        foreach ($this->harvestItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
        }
        return $result;
    }

    /**
     * Calcula el total de insumos separado por development_state.
     * Usa datos cacheados - sin queries.
     */
    private function getSupplyResultByDevelopmentState($value, $costCentersId)
    {
        $result = [];
        $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;

        foreach ($this->supplyItemIndex[$value->id] ?? [] as $costCenterId => $monthData) {
            $center = $this->cachedCenterData[$costCenterId] ?? null;
            if (!$center) continue;

            $surface = $center['surface'];
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($value->price * $quantityFirst, 2);
            $activeMonths = count($monthData);
            $totalAmount = $amountFirst * $activeMonths;

            $fruitIdStr = (string)$center['fruit_id'];
            $devStateIdStr = (string)$center['development_state_id'];
            if (!isset($result[$fruitIdStr][$devStateIdStr])) {
                $result[$fruitIdStr][$devStateIdStr] = 0;
            }
            $result[$fruitIdStr][$devStateIdStr] += $totalAmount;
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
    private function getAdministrationTotalsByLevel12($team_id = null, $branchId = null)
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
        if ($branchId) {
            $administrations->where('a.branch_id', $branchId);
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

    private function getFieldTotalsByLevel12($team_id = null, $branchId = null)
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
        if ($branchId) {
            $fields->where('a.branch_id', $branchId);
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
     * Obtiene los totales generales agrupados por Level 1, Level 2 y fruit.
     * OPTIMIZADO: usa datos cacheados + batch items queries (elimina N+1).
     */
    private function getTotalsByLevel12($team_id = null)
    {
        $season_id = session('season_id');
        $months = $this->cachedMonths;
        $costCentersIds = $this->cachedCostCenters->pluck('id')->toArray();
        $fruitIds = $this->cachedCostCenters->pluck('fruit_id')->unique()->filter()->values();
        $fruitNames = $fruitIds->isNotEmpty() ? \App\Models\Fruit::whereIn('id', $fruitIds->toArray())->pluck('name','id') : collect();

        $totals = [];
        $addTotal = function($level1_id, $level1_name, $level2_id, $level2_name, $fruit_id, $amount, $surface = null) use (&$totals, $fruitNames) {
            $key = $level1_id.'-'.$level2_id.'-'.$fruit_id;
            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'level1_id' => $level1_id, 'level1_name' => $level1_name,
                    'level2_id' => $level2_id, 'level2_name' => $level2_name,
                    'fruit_id' => $fruit_id,
                    'fruit_name' => $fruit_id && isset($fruitNames[$fruit_id]) ? $fruitNames[$fruit_id] : null,
                    'total_amount' => 0, 'surface' => $surface
                ];
            } else if ($surface !== null) {
                $totals[$key]['surface'] = ($totals[$key]['surface'] ?? 0) + $surface;
            }
            $totals[$key]['total_amount'] += $amount;
        };

        // AGROCHEMICALS - batch items query
        $agrochemicals = \App\Models\Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->join('level3s as l3', 'a.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('a.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'ai.cost_center_id')
            ->whereIn('ai.cost_center_id', $costCentersIds)
            ->groupBy('a.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'ai.cost_center_id')
            ->get();
        $agroItemIndex = $this->buildItemIndex('agrochemical_items', 'agrochemical_id', $agrochemicals->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($agrochemicals as $a) {
            $center = $this->cachedCenterData[$a->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $dose = (($a->unit_id == 4 && $a->unit_id_price == 3) || ($a->unit_id == 2 && $a->unit_id_price == 1)) ? ($a->dose / 1000) : $a->dose;
            if ($a->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($a->dose_type_id == 2) {
                $quantityFirst = round((($a->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            $amountFirst = round($a->price * $quantityFirst, 2);
            $activeMonths = isset($agroItemIndex[$a->id][$a->cost_center_id]) ? count($agroItemIndex[$a->id][$a->cost_center_id]) : 0;
            $addTotal($a->level1_id, $a->level1_name, $a->level2_id, $a->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        // FERTILIZERS - batch items query
        $fertilizers = \App\Models\Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('level3s as l3', 'f.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('f.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'fi.cost_center_id')
            ->whereIn('fi.cost_center_id', $costCentersIds)
            ->groupBy('f.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'fi.cost_center_id')
            ->get();
        $fertItemIndex = $this->buildItemIndex('fertilizer_items', 'fertilizer_id', $fertilizers->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($fertilizers as $f) {
            $center = $this->cachedCenterData[$f->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $dose = (($f->unit_id == 4 && $f->unit_id_price == 3) || ($f->unit_id == 2 && $f->unit_id_price == 1)) ? ($f->dose / 1000) : $f->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($f->price * $quantityFirst, 2);
            $activeMonths = isset($fertItemIndex[$f->id][$f->cost_center_id]) ? count($fertItemIndex[$f->id][$f->cost_center_id]) : 0;
            $addTotal($f->level1_id, $f->level1_name, $f->level2_id, $f->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        // MANPOWER - batch items query
        $manpowers = \App\Models\ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->join('level3s as l3', 'mp.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('mp.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'mpi.cost_center_id')
            ->whereIn('mpi.cost_center_id', $costCentersIds)
            ->groupBy('mp.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'mpi.cost_center_id')
            ->get();
        $mpItemIndex = $this->buildItemIndex('manpower_items', 'man_power_id', $manpowers->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($manpowers as $mp) {
            $center = $this->cachedCenterData[$mp->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantityFirst = round($mp->workday * $surface, 2);
            $amountFirst = round($mp->price * $quantityFirst, 2);
            $activeMonths = isset($mpItemIndex[$mp->id][$mp->cost_center_id]) ? count($mpItemIndex[$mp->id][$mp->cost_center_id]) : 0;
            $addTotal($mp->level1_id, $mp->level1_name, $mp->level2_id, $mp->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        // SERVICES - batch items query
        $services = \App\Models\Service::from('services as s')
            ->join('service_items as si', 's.id', 'si.service_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCentersIds)
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        $svcItemIndex = $this->buildItemIndex('service_items', 'service_id', $services->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($services as $s) {
            $center = $this->cachedCenterData[$s->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($s->unit_id == 4 && $s->unit_id_price == 3) || ($s->unit_id == 2 && $s->unit_id_price == 1)) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            $activeMonths = isset($svcItemIndex[$s->id][$s->cost_center_id]) ? count($svcItemIndex[$s->id][$s->cost_center_id]) : 0;
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        // HARVESTS - batch items query
        $harvests = \App\Models\Harvest::from('harvests as s')
            ->join('harvest_items as si', 's.id', 'si.harvest_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCentersIds)
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        $harvItemIndex = $this->buildItemIndex('harvest_items', 'harvest_id', $harvests->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($harvests as $s) {
            $center = $this->cachedCenterData[$s->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($s->unit_id == 4 && $s->unit_id_price == 3) || ($s->unit_id == 2 && $s->unit_id_price == 1)) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            $activeMonths = isset($harvItemIndex[$s->id][$s->cost_center_id]) ? count($harvItemIndex[$s->id][$s->cost_center_id]) : 0;
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        // SUPPLIES - batch items query
        $supplies = \App\Models\Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'si.cost_center_id')
            ->whereIn('si.cost_center_id', $costCentersIds)
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'si.cost_center_id')
            ->get();
        $supItemIndex = $this->buildItemIndex('supply_items', 'supply_id', $supplies->pluck('id')->unique(), $costCentersIds, $months);
        foreach ($supplies as $s) {
            $center = $this->cachedCenterData[$s->cost_center_id] ?? null;
            if (!$center) continue;
            $surface = $center['surface'];
            $quantity = (($s->unit_id == 4 && $s->unit_id_price == 3) || ($s->unit_id == 2 && $s->unit_id_price == 1)) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            $activeMonths = isset($supItemIndex[$s->id][$s->cost_center_id]) ? count($supItemIndex[$s->id][$s->cost_center_id]) : 0;
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $center['fruit_id'], $amountFirst * $activeMonths, $surface);
        }

        return collect(array_values($totals));
    }

    /**
     * Helper: construye un índice de items [entity_id][cost_center_id][month_id] = true
     * Reemplaza N×12 COUNT queries individuales por 1 batch query.
     */
    private function buildItemIndex($table, $idColumn, $entityIds, $costCentersIds, $months)
    {
        if ($entityIds->isEmpty()) return [];
        $items = DB::table($table)
            ->select($idColumn, 'cost_center_id', 'month_id')
            ->whereIn($idColumn, $entityIds)
            ->whereIn('cost_center_id', $costCentersIds)
            ->whereIn('month_id', $months)
            ->groupBy($idColumn, 'cost_center_id', 'month_id')
            ->get();
        $index = [];
        foreach ($items as $item) {
            $index[$item->$idColumn][$item->cost_center_id][$item->month_id] = true;
        }
        return $index;
    }

    /**
     * Totales mensuales de administración.
     * OPTIMIZADO: 1 batch query para items en lugar de N queries individuales.
     */
    private function getMonthsAdministration($team_id = null, $branchId = null)
    {
        $season_id = session('season_id');
        $months = $this->cachedMonths;
        $result = array_fill_keys($months, 0);

        $administrations = DB::table('administrations as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        if ($branchId) {
            $administrations->where('a.branch_id', $branchId);
        }
        $administrations = $administrations->get();

        if ($administrations->isEmpty()) return $result;

        // Batch: traer TODOS los items de todas las administraciones en 1 query
        $allItems = DB::table('administration_items')
            ->whereIn('administration_id', $administrations->pluck('id'))
            ->whereIn('month_id', $months)
            ->get();

        // Indexar items por administration_id
        $itemsByAdm = [];
        foreach ($allItems as $item) {
            $itemsByAdm[$item->administration_id][] = $item;
        }

        foreach ($administrations as $adm) {
            $quantity = ($adm->quantity !== null && $adm->quantity > 0)
                ? (in_array($adm->unit_id ?? null, [2, 4]) ? ($adm->quantity / 1000) : $adm->quantity)
                : 0;
            $amount = round($adm->price * $quantity, 2);
            foreach ($itemsByAdm[$adm->id] ?? [] as $item) {
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }

    /**
     * Totales mensuales de fields.
     * OPTIMIZADO: 1 batch query para items en lugar de N queries individuales.
     */
    private function getMonthsFields($team_id = null, $branchId = null)
    {
        $season_id = session('season_id');
        $months = $this->cachedMonths;
        $result = array_fill_keys($months, 0);

        $fields = DB::table('fields as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        if ($branchId) {
            $fields->where('a.branch_id', $branchId);
        }
        $fields = $fields->get();

        if ($fields->isEmpty()) return $result;

        // Batch: traer TODOS los items de todos los fields en 1 query
        $allItems = DB::table('field_items')
            ->whereIn('field_id', $fields->pluck('id'))
            ->whereIn('month_id', $months)
            ->get();

        // Indexar items por field_id
        $itemsByField = [];
        foreach ($allItems as $item) {
            $itemsByField[$item->field_id][] = $item;
        }

        foreach ($fields as $f) {
            $quantity = ($f->quantity !== null && $f->quantity > 0)
                ? (in_array($f->unit_id ?? null, [2, 4]) ? ($f->quantity / 1000) : $f->quantity)
                : 0;
            $amount = round($f->price * $quantity, 2);
            foreach ($itemsByField[$f->id] ?? [] as $item) {
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }
}

