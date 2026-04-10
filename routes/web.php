<?php


    // Rutas para Notas de Crédito/Débito
    use App\Http\Controllers\CreditDebitNotes\CreateCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotes\StoreCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotes\ShowCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotes\EditCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotes\UpdateCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotes\DeleteCreditDebitNoteController;
    use App\Http\Controllers\CreditDebitNotesController;

use App\Http\Controllers\Products2\StoreProduct2Controller;
use App\Http\Controllers\Products2\UpdateProduct2Controller;
use App\Http\Controllers\Products2\DeleteProduct2Controller;

 // Rutas para Fuel Outflows
    use App\Http\Controllers\FuelOutflowController;
    use App\Http\Controllers\FuelOutflows\StoreFuelOutflowController;
    use App\Http\Controllers\FuelOutflows\ShowFuelOutflowController;
    use App\Http\Controllers\FuelOutflows\EditFuelOutflowController;
    use App\Http\Controllers\FuelOutflows\UpdateFuelOutflowController;
    use App\Http\Controllers\FuelOutflows\DeleteFuelOutflowController;

// Rutas para Operators
    use App\Http\Controllers\OperatorsController;
    use App\Http\Controllers\Operators\StoreOperatorController;
    use App\Http\Controllers\Operators\UpdateOperatorController;
    use App\Http\Controllers\Operators\DeleteOperatorController;

// Rutas para Application Orders
    use App\Http\Controllers\ApplicationOrdersController;
    use App\Http\Controllers\ApplicationOrders\StoreApplicationOrderController;
    use App\Http\Controllers\ApplicationOrders\ShowApplicationOrderController;
    use App\Http\Controllers\ApplicationOrders\UpdateApplicationOrderController;
    use App\Http\Controllers\ApplicationOrders\DeleteApplicationOrderController;
    use App\Http\Controllers\ApplicationOrders\PdfApplicationOrderController;

// Rutas API
    use App\Http\Controllers\Api\GetProductsController;
    use App\Http\Controllers\Api\GetPendingExpenseReportItemsController;
    use App\Http\Controllers\Api\StoreSupplierApiController;
    use App\Http\Controllers\Api\GetCostCenterVarietiesController;

// Rutas AI Chat
    use App\Http\Controllers\AiChat\AiDatabaseChatController;

// Rutas para Irrigation Pumps
    use App\Http\Controllers\IrrigationPumpsController;
    use App\Http\Controllers\IrrigationPumps\StoreIrrigationPumpController;
    use App\Http\Controllers\IrrigationPumps\UpdateIrrigationPumpController;
    use App\Http\Controllers\IrrigationPumps\DeleteIrrigationPumpController;

// Rutas para Fertilizer Orders
    use App\Http\Controllers\FertilizerOrdersController;
    use App\Http\Controllers\FertilizerOrders\StoreFertilizerOrderController;
    use App\Http\Controllers\FertilizerOrders\ShowFertilizerOrderController;
    use App\Http\Controllers\FertilizerOrders\UpdateFertilizerOrderController;
    use App\Http\Controllers\FertilizerOrders\DeleteFertilizerOrderController;
    use App\Http\Controllers\FertilizerOrders\PdfFertilizerOrderController;

// Rutas para Fertilizer Outflows
    use App\Http\Controllers\FertilizerOutflows\FertilizerOutflowController;
    use App\Http\Controllers\FertilizerOutflows\StoreFertilizerOutflowController;
    use App\Http\Controllers\FertilizerOutflows\EditFertilizerOutflowController;
    use App\Http\Controllers\FertilizerOutflows\UpdateFertilizerOutflowController;
    use App\Http\Controllers\FertilizerOutflows\DeleteFertilizerOutflowController;

// Rutas para Agrochemical Outflows
    use App\Http\Controllers\AgrochemicalOutflows\AgrochemicalOutflowController;
    use App\Http\Controllers\AgrochemicalOutflows\StoreAgrochemicalOutflowController;
    use App\Http\Controllers\AgrochemicalOutflows\UpdateAgrochemicalOutflowController;
    use App\Http\Controllers\AgrochemicalOutflows\DeleteAgrochemicalOutflowController;
    use App\Http\Controllers\AgrochemicalOutflows\RevertAgrochemicalOutflowController;

// Rutas para Libro de Campo
    use App\Http\Controllers\LibroCampoController;

// Rutas para Invoice Payments
    use App\Http\Controllers\InvoicePayments\InvoicePaymentController;
    use App\Http\Controllers\InvoicePayments\InvoicePaymentDashboardController;
    use App\Http\Controllers\InvoicePayments\StoreInvoicePaymentController;
    use App\Http\Controllers\InvoicePayments\UpdateInvoicePaymentController;
    use App\Http\Controllers\InvoicePayments\DeleteInvoicePaymentController;

// Rutas para Rendiciones de Gastos
    use App\Http\Controllers\ExpenseReports\ExpenseReportController;
    use App\Http\Controllers\ExpenseReports\StoreExpenseReportController;
    use App\Http\Controllers\ExpenseReports\ShowExpenseReportController;
    use App\Http\Controllers\ExpenseReports\DeleteExpenseReportController;
    use App\Http\Controllers\ExpenseReports\StoreExpenseReportItemController;
    use App\Http\Controllers\ExpenseReports\DeleteExpenseReportItemController;
    use App\Http\Controllers\ExpenseReports\UpdateExpenseReportStatusController;
    use App\Http\Controllers\ExpenseReports\ApproveExpenseReportController;
    use App\Http\Controllers\ExpenseReports\RejectExpenseReportController;
    use App\Http\Controllers\ExpenseReports\ExportExpenseReportsController;

// Rutas para Purchase Orders
    use App\Http\Controllers\PurchaseOrders\PurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\StorePurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\UpdatePurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\DeletePurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\ShowPurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\UpdatePurchaseOrderStatusController;
    use App\Http\Controllers\PurchaseOrders\ApprovePurchaseOrderController;
    use App\Http\Controllers\PurchaseOrders\RejectPurchaseOrderController;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemGuideController;
use App\Http\Controllers\TechnicalPanelController;
use App\Http\Controllers\AgrochemicalsController;
use App\Http\Controllers\FertilizersController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\BudgetsController;
use App\Http\Controllers\CostCentersController;
use App\Http\Controllers\SelectBudgetController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CompanyReasonsController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\LevelsController;
use App\Http\Controllers\VarietiesController;
use App\Http\Controllers\FruitsController;
use App\Http\Controllers\ManPowersController;
use App\Http\Controllers\ParcelsController;
use App\Http\Controllers\SeasonsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MachineriesController;
use App\Http\Controllers\TypeMachineriesController;
use App\Http\Controllers\SuppliesController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\AdministrationsController;
use App\Http\Controllers\FieldsController;
use App\Http\Controllers\Teams\StoreTeamController;
use App\Http\Controllers\Teams\UpdateTeamController;
use App\Http\Controllers\Teams\DeleteTeamController;
use App\Http\Controllers\Teams\ActivateInactivateTeamController;
use App\Http\Controllers\Users\StoreUserController;
use App\Http\Controllers\Users\UpdateUserController;
use App\Http\Controllers\Users\DeleteUserController;
use App\Http\Controllers\Users\ActiveInactiveUserController;
use App\Http\Controllers\Budgets\StoreBudgetController;
use App\Http\Controllers\Budgets\UpdateBudgetController;
use App\Http\Controllers\Budgets\DeleteBudgetController;
use App\Http\Controllers\Seasons\SaveSeasonController;
use App\Http\Controllers\Seasons\SetDefaultSeasonController;
use App\Http\Controllers\CostCenters\StoreCostCenterController;
use App\Http\Controllers\CostCenters\UpdateCostCenterController;
use App\Http\Controllers\CostCenters\DeleteCostCenterController;
use App\Http\Controllers\CostCenters\CopyCostCentersController;
use App\Http\Controllers\Agrochemicals\StoreAgrochemicalController;
use App\Http\Controllers\Agrochemicals\UpdateAgrochemicalController;
use App\Http\Controllers\Agrochemicals\DeleteAgrochemicalController;
use App\Http\Controllers\Fertilizers\StoreFertilizerController;
use App\Http\Controllers\Fertilizers\UpdateFertilizerController;
use App\Http\Controllers\Fertilizers\DeleteFertilizerController;
use App\Http\Controllers\ManPowers\StoreManPowerController;
use App\Http\Controllers\ManPowers\UpdateManPowerController;
use App\Http\Controllers\ManPowers\DeleteManPowerController;
use App\Http\Controllers\Suppliers\StoreSupplierController;
use App\Http\Controllers\Suppliers\UpdateSupplierController;
use App\Http\Controllers\Suppliers\DeleteSupplierController;
use App\Http\Controllers\Products\StoreProductController;
use App\Http\Controllers\Products\UpdateProductController;
use App\Http\Controllers\Products\DeleteProductController;
use App\Http\Controllers\CompanyReasons\StoreCompanyReasonController;
use App\Http\Controllers\CompanyReasons\UpdateCompanyReasonController;
use App\Http\Controllers\CompanyReasons\DeleteCompanyReasonController;
use App\Http\Controllers\Invoices\CreateInvoiceController;
use App\Http\Controllers\Invoices\StoreInvoiceController;
use App\Http\Controllers\Invoices\ShowInvoiceController;
use App\Http\Controllers\Invoices\EditInvoiceController;
use App\Http\Controllers\Invoices\UpdateInvoiceController;
use App\Http\Controllers\Invoices\DeleteInvoiceController;
use App\Http\Controllers\Invoices\DuplicateInvoiceController;
use App\Http\Controllers\Invoices\ExtractInvoiceFromPdfController;
use App\Http\Controllers\Level2s\GetLevel2Controller;
use App\Http\Controllers\Level3s\GetLevel3Controller;
use App\Http\Controllers\Level4s\GetLevel4Controller;
use App\Http\Controllers\Levels\StoreLevelController;
use App\Http\Controllers\Levels\UpdateLevelController;
use App\Http\Controllers\Levels\DeleteLevelController;
use App\Http\Controllers\Levels\Level2Controller;
use App\Http\Controllers\Levels\Level3Controller;
use App\Http\Controllers\Levels\Level4Controller;
use App\Http\Controllers\Level2s\StoreLevel2Controller;
use App\Http\Controllers\Level2s\UpdateLevel2Controller;
use App\Http\Controllers\Level2s\DeleteLevel2Controller;
use App\Http\Controllers\Level3s\StoreLevel3Controller;
use App\Http\Controllers\Level3s\UpdateLevel3Controller;
use App\Http\Controllers\Level3s\DeleteLevel3Controller;
use App\Http\Controllers\Level3s\ImportLevel3Controller;
use App\Http\Controllers\Level4s\StoreLevel4Controller;
use App\Http\Controllers\Level4s\UpdateLevel4Controller;
use App\Http\Controllers\Level4s\DeleteLevel4Controller;
use App\Http\Controllers\Fruits\StoreFruitController;
use App\Http\Controllers\Fruits\UpdateFruitController;
use App\Http\Controllers\Fruits\DeleteFruitController;
use App\Http\Controllers\PhenologicalStagesController;
use App\Http\Controllers\PhenologicalStages\StorePhenologicalStageController;
use App\Http\Controllers\PhenologicalStages\UpdatePhenologicalStageController;
use App\Http\Controllers\PhenologicalStages\DeletePhenologicalStageController;
use App\Http\Controllers\Varieties\GetVarietyController;
use App\Http\Controllers\Varieties\StoreVarietyController;
use App\Http\Controllers\Varieties\UpdateVarietyController;
use App\Http\Controllers\Varieties\DeleteVarietyController;
// Rutas para Rootstocks (portainjertos)
use App\Http\Controllers\Rootstocks\RootstockController;
use App\Http\Controllers\Rootstocks\StoreRootstockController;
use App\Http\Controllers\Rootstocks\UpdateRootstockController;
use App\Http\Controllers\Rootstocks\DeleteRootstockController;
// Rutas para CostCenterVarieties (variedades por cuartel)
use App\Http\Controllers\CostCenterVarieties\CostCenterVarietyController;
use App\Http\Controllers\CostCenterVarieties\StoreCostCenterVarietyController;
use App\Http\Controllers\CostCenterVarieties\UpdateCostCenterVarietyController;
use App\Http\Controllers\CostCenterVarieties\DeleteCostCenterVarietyController;
// Rutas para Production Dispatches (despachos de producción)
use App\Http\Controllers\ProductionDispatches\ProductionDispatchController;
use App\Http\Controllers\ProductionDispatches\StoreProductionDispatchController;
use App\Http\Controllers\ProductionDispatches\UpdateProductionDispatchController;
use App\Http\Controllers\ProductionDispatches\DeleteProductionDispatchController;
use App\Http\Controllers\ProductionDispatches\ProcessProductionDispatchController;
// Rutas para Production Summaries (resumen de producción)
use App\Http\Controllers\ProductionSummaries\ProductionSummaryController;
use App\Http\Controllers\ProductionSummaries\StoreProductionSummaryController;
use App\Http\Controllers\ProductionSummaries\UpdateProductionSummaryController;
use App\Http\Controllers\ProductionSummaries\DeleteProductionSummaryController;
// Rutas para Exporters (exportadoras)
use App\Http\Controllers\Exporters\StoreExporterController;
use App\Http\Controllers\Exporters\UpdateExporterController;
use App\Http\Controllers\Exporters\DeleteExporterController;
// Rutas para Packing Houses
use App\Http\Controllers\PackingHouses\StorePackingHouseController;
use App\Http\Controllers\PackingHouses\UpdatePackingHouseController;
use App\Http\Controllers\PackingHouses\DeletePackingHouseController;
// Rutas para Bin Types
use App\Http\Controllers\BinTypes\StoreBinTypeController;
use App\Http\Controllers\BinTypes\UpdateBinTypeController;
use App\Http\Controllers\BinTypes\DeleteBinTypeController;
// Rutas para Box Types
use App\Http\Controllers\BoxTypes\StoreBoxTypeController;
use App\Http\Controllers\BoxTypes\UpdateBoxTypeController;
use App\Http\Controllers\BoxTypes\DeleteBoxTypeController;
// Rutas para Carriers (Transportistas)
use App\Http\Controllers\Carriers\StoreCarrierController;
use App\Http\Controllers\Carriers\UpdateCarrierController;
use App\Http\Controllers\Carriers\DeleteCarrierController;
// Rutas para Employees (Colaboradores - Remuneraciones)
use App\Http\Controllers\Employees\EmployeeController;
use App\Http\Controllers\Employees\StoreEmployeeController;
use App\Http\Controllers\Employees\UpdateEmployeeController;
use App\Http\Controllers\Employees\DeleteEmployeeController;
// Rutas para Contracts (Contratos - Remuneraciones)
use App\Http\Controllers\Contracts\ContractController;
use App\Http\Controllers\Contracts\StoreContractController;
use App\Http\Controllers\Contracts\UpdateContractController;
use App\Http\Controllers\Contracts\DeleteContractController;
// Rutas para Labor Types (Tipos de Labor - Remuneraciones)
use App\Http\Controllers\LaborTypes\LaborTypeController;
use App\Http\Controllers\LaborTypes\StoreLaborTypeController;
use App\Http\Controllers\LaborTypes\UpdateLaborTypeController;
use App\Http\Controllers\LaborTypes\DeleteLaborTypeController;
use App\Http\Controllers\LaborTypes\ExportLaborTypesPdfController;
use App\Http\Controllers\LaborTypes\ShowLaborTypesCatalogController;
// Rutas para Bonus Types (Tipos de Bono - Remuneraciones)
use App\Http\Controllers\BonusTypes\BonusTypeController;
use App\Http\Controllers\BonusTypes\StoreBonusTypeController;
use App\Http\Controllers\BonusTypes\UpdateBonusTypeController;
use App\Http\Controllers\BonusTypes\DeleteBonusTypeController;
// Rutas para Labor Rates (Tarifas de Labor - Remuneraciones)
use App\Http\Controllers\LaborRates\LaborRateController;
use App\Http\Controllers\LaborRates\StoreLaborRateController;
use App\Http\Controllers\LaborRates\UpdateLaborRateController;
use App\Http\Controllers\LaborRates\DeleteLaborRateController;
use App\Http\Controllers\LaborRates\ExportLaborRatesPdfController;
use App\Http\Controllers\LaborRates\ShowLaborRatesCatalogController;
// Rutas para Daily Attendances (Asistencia Diaria - Remuneraciones)
use App\Http\Controllers\DailyAttendances\DailyAttendanceController;
use App\Http\Controllers\DailyAttendances\StoreDailyAttendanceController;
use App\Http\Controllers\DailyAttendances\DeleteDailyAttendanceController;
// Rutas para Daily Yields (Tarjas Diarias - Remuneraciones)
use App\Http\Controllers\DailyYields\DailyYieldController;
use App\Http\Controllers\DailyYields\StoreDailyYieldController;
use App\Http\Controllers\DailyYields\UpdateDailyYieldController;
use App\Http\Controllers\DailyYields\DeleteDailyYieldController;
// Rutas para Work Schedules (Horario de Trabajo - Remuneraciones)
use App\Http\Controllers\WorkSchedules\WorkScheduleController;
use App\Http\Controllers\WorkSchedules\UpdateWorkScheduleController;
// Rutas para Daily Management (Gestión Diaria - Hub Remuneraciones)
use App\Http\Controllers\DailyManagement\DailyManagementController;
use App\Http\Controllers\DailyManagement\MonthlyReportController;
use App\Http\Controllers\DailyManagement\ExportMonthlyExcelController;
use App\Http\Controllers\DailyManagement\ExportMonthlyPdfController;
// Rutas para Project Evaluations (Evaluación de Proyectos Agrícolas)
use App\Http\Controllers\ProjectEvaluations\ProjectEvaluationController;
use App\Http\Controllers\ProjectEvaluations\ShowProjectEvaluationController;
use App\Http\Controllers\ProjectEvaluations\StoreProjectEvaluationController;
use App\Http\Controllers\ProjectEvaluations\UpdateProjectEvaluationController;
use App\Http\Controllers\ProjectEvaluations\DeleteProjectEvaluationController;
use App\Http\Controllers\ProjectEvaluations\StoreProjectEvaluationRowController;
use App\Http\Controllers\ProjectEvaluations\StoreBulkProjectEvaluationRowsController;
use App\Http\Controllers\ProjectEvaluations\UpdateProjectEvaluationRowController;
use App\Http\Controllers\ProjectEvaluations\DeleteProjectEvaluationRowController;
use App\Http\Controllers\ProjectEvaluations\UpsertRnpPricesController;
use App\Http\Controllers\ProjectEvaluations\UpsertVarietyCostParamsController;
use App\Http\Controllers\ProjectEvaluations\UpsertKgYieldCostsController;

// Rutas para ContractTemplates (Plantillas de Contrato)
use App\Http\Controllers\ContractTemplates\ContractTemplateController;
use App\Http\Controllers\ContractTemplates\StoreContractTemplateController;
use App\Http\Controllers\ContractTemplates\DeleteContractTemplateController;
use App\Http\Controllers\ContractTemplates\GenerateContractController;
use App\Http\Controllers\Api\ScheduleApiController;
use App\Http\Controllers\Api\CityApiController;
use App\Http\Controllers\Api\UpdateDollarPriceController;
// API para refrescar selects de producción
use App\Http\Controllers\Api\GetExportersController;
use App\Http\Controllers\Api\GetPackingHousesController;
use App\Http\Controllers\Api\GetBinTypesController;
use App\Http\Controllers\Api\GetBoxTypesController;
use App\Http\Controllers\Api\GetCarriersController;
use App\Http\Controllers\Api\FruitClassificationController;
use App\Http\Controllers\Parcels\StoreParcelController;
use App\Http\Controllers\Parcels\UpdateParcelController;
use App\Http\Controllers\Parcels\DeleteParcelController;
use App\Http\Controllers\Parcels\TransferParcelsController;
use App\Http\Controllers\Seasons\StoreSeasonController;
use App\Http\Controllers\Seasons\UpdateSeasonController;
use App\Http\Controllers\Seasons\DeleteSeasonController;
use App\Http\Controllers\Seasons\CopyBudgetController;
use App\Http\Controllers\Machineries\StoreMachineryController;
use App\Http\Controllers\Machineries\UpdateMachineryController;
use App\Http\Controllers\Machineries\DeleteMachineryController;
use App\Http\Controllers\TypeMachineries\StoreTypeMachineryController;
use App\Http\Controllers\TypeMachineries\UpdateTypeMachineryController;
use App\Http\Controllers\TypeMachineries\DeleteTypeMachineryController;
use App\Http\Controllers\Supplies\StoreSupplyController;
use App\Http\Controllers\Supplies\UpdateSupplyController;
use App\Http\Controllers\Supplies\DeleteSupplyController;
use App\Http\Controllers\Services\StoreServiceController;
use App\Http\Controllers\Services\DeleteServiceController;
use App\Http\Controllers\Services\UpdateServiceController;
use App\Http\Controllers\Pdfs\BudgetsPdfController;
use App\Http\Controllers\Pdfs\CostCentersPdfController;
use App\Http\Controllers\Pdfs\LevelsPdfController;
use App\Http\Controllers\Pdfs\Levels2PdfController;
use App\Http\Controllers\Pdfs\Levels3PdfController;
use App\Http\Controllers\Pdfs\Levels4PdfController;
use App\Http\Controllers\Pdfs\UsersPdfController;
use App\Http\Controllers\Pdfs\CompanyReasonsPdfController;
use App\Http\Controllers\Pdfs\FruitsPdfController;
use App\Http\Controllers\Pdfs\ParcelsPdfController;
use App\Http\Controllers\Pdfs\VarietiesPdfController;
use App\Http\Controllers\Pdfs\SeasonsPdfController;
use App\Http\Controllers\Pdfs\SuppliersPdfController;
use App\Http\Controllers\Pdfs\ProductsPdfController;
use App\Http\Controllers\Pdfs\InvoicesPdfController;
use App\Http\Controllers\Pdfs\MachineriesPdfController;
use App\Http\Controllers\Pdfs\TypeMachineriesPdfController;
use App\Http\Controllers\Excels\BudgetsExcelController;
use App\Http\Controllers\Excels\CostCentersExcelController;
use App\Http\Controllers\Excels\LevelsExcelController;
use App\Http\Controllers\Excels\Levels2ExcelController;
use App\Http\Controllers\Excels\Levels3ExcelController;
use App\Http\Controllers\Excels\Levels4ExcelController;
use App\Http\Controllers\Excels\UsersExcelController;
use App\Http\Controllers\Excels\CompanyReasonsExcelController;
use App\Http\Controllers\Excels\FruitsExcelController;
use App\Http\Controllers\Excels\ParcelsExcelController;
use App\Http\Controllers\Excels\VarietiesExcelController;
use App\Http\Controllers\Excels\SeasonsExcelController;
use App\Http\Controllers\Excels\SuppliersExcelController;
use App\Http\Controllers\Excels\ProductsExcelController;
use App\Http\Controllers\Excels\InvoicesExcelController;
use App\Http\Controllers\Excels\InvoicePaymentsExcelController;
use App\Http\Controllers\Excels\MachineriesExcelController;
use App\Http\Controllers\Excels\TypeMachineriesExcelController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\Administrations\StoreAdministrationController;
use App\Http\Controllers\Administrations\DeleteAdministrationController;
use App\Http\Controllers\Administrations\UpdateAdministrationController;
use App\Http\Controllers\Fields\StoreFieldController;
use App\Http\Controllers\Fields\UpdateFieldController;
use App\Http\Controllers\Fields\DeleteFieldController;
use App\Http\Controllers\SidebarController;
use App\Http\Controllers\EstimatesController;
use App\Http\Controllers\Estimates\StoreEstimateController;
use App\Http\Controllers\Estimates\DeleteEstimateController;
use App\Http\Controllers\Estimates\UpdateEstimateController;
use App\Http\Controllers\Harvests\StoreHarvestController;
use App\Http\Controllers\Harvests\DeleteHarvestController;
use App\Http\Controllers\Harvests\UpdateHarvestController;
use App\Http\Controllers\HarvestsController;
use App\Http\Controllers\Product2Controller;
use App\Http\Controllers\Groupings\StoreGroupingController;
use App\Http\Controllers\Groupings\UpdateGroupingController;
use App\Http\Controllers\Groupings\DeleteGroupingController;
use App\Http\Controllers\GroupingsController;
use App\Http\Controllers\OutflowsController;
use App\Http\Controllers\OutflowsDashboardController;
use App\Http\Controllers\ComparativeOutflowsDashboardController;
use App\Http\Controllers\HectareDashboardController;
use App\Http\Controllers\Outflows\CreateOutflowController;
use App\Http\Controllers\Outflows\StoreOutflowController;
use App\Http\Controllers\Outflows\ShowOutflowController;
use App\Http\Controllers\Outflows\EditOutflowController;
use App\Http\Controllers\Outflows\UpdateOutflowController;
use App\Http\Controllers\Outflows\DeleteOutflowController;
use App\Http\Controllers\Outflows\GetLevel3SuggestionsController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\Investments\IndexInvestmentController;
use App\Http\Controllers\Investments\StoreInvestmentController;
use App\Http\Controllers\Investments\UpdateInvestmentController;
use App\Http\Controllers\Investments\DeleteInvestmentController;
// Rutas para Projects
use App\Http\Controllers\Projects\IndexProjectController;
use App\Http\Controllers\Projects\StoreProjectController;
use App\Http\Controllers\Projects\UpdateProjectController;
use App\Http\Controllers\Projects\DeleteProjectController;
use App\Http\Controllers\ProductStockLinesController;
use App\Http\Controllers\ConsolidatedDocumentsController;
use App\Http\Controllers\ConsolidatedOutflowsController;
use App\Http\Controllers\InventoryController;

use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Override Fortify: password reset busca por username en vez de email
Route::post('/forgot-password', \App\Http\Controllers\Auth\ForgotPasswordController::class)
    ->middleware('guest')
    ->name('password.email');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {


    // Página de Preguntas Frecuentes (FAQ)
    Route::get('/faq', function () {
        return Inertia::render('FaqPage');
    })->name('faq');

    // Ruta para crear un nuevo estado de estimación desde el frontend
    Route::post('/estimate-status', [EstimatesController::class, 'storeEstimateStatus'])->name('estimate-status.store');

    // API para refrescar selects
    Route::get('/api/products', GetProductsController::class)->name('api.products');
    Route::patch('/api/dollar-price', UpdateDollarPriceController::class)->name('api.dollar-price.update');
    Route::get('/api/pending-expense-items', GetPendingExpenseReportItemsController::class)->name('api.pending-expense-items');
    Route::post('/api/suppliers', StoreSupplierApiController::class)->name('api.suppliers.store');
    Route::get('/api/cost-center-varieties/{costCenterId}', GetCostCenterVarietiesController::class)->name('api.cost-center-varieties');

    // AI Chat - consultas en lenguaje natural sobre la base de datos
    Route::post('/api/ai-chat', AiDatabaseChatController::class)->name('api.ai-chat');

    // Products2 estilo teams: vista única y controladores separados para acciones

    Route::get('/products2', [Product2Controller::class, 'index'])->name('products2.index');
    Route::post('/products2', StoreProduct2Controller::class)->name('products2.store');
    Route::post('/products2/{products2}/update', UpdateProduct2Controller::class)->name('products2.update');
    Route::post('/products2/{products2}/delete', DeleteProduct2Controller::class)->name('products2.destroy');

    Route::post('/sidebar/has-variety-for-season', [SidebarController::class, 'hasVarietyForSeason'])->name('sidebar.hasVarietyForSeason');
    Route::post('/sidebar/has-fruit-for-season', [SidebarController::class, 'hasFruitForSeason'])->name('sidebar.hasFruitForSeason');
    Route::post('/sidebar/has-costcenter-for-season', [SidebarController::class, 'hasCostCenterForSeason'])->name('sidebar.hasCostCenterForSeason');
    Route::post('/sidebar/has-companyreason-for-team', [SidebarController::class, 'hasCompanyReasonForTeam'])->name('sidebar.hasCompanyReasonForTeam');
    Route::post('/sidebar/has-season-for-team', [SidebarController::class, 'hasSeasonForTeam'])->name('sidebar.hasSeasonForTeam');
    Route::post('/sidebar/has-parcel-for-team', [SidebarController::class, 'hasParcelForTeam'])->name('sidebar.hasParcelForTeam');
    Route::post('/sidebar/has-level3-for-level2', [SidebarController::class, 'hasLevel3ForLevel2'])->name('sidebar.hasLevel3ForLevel2');


    Route::get('/teams', TeamsController::class)->name('teams.index');
    Route::get('/login-logs', LoginLogController::class)->name('login-logs.index');
    Route::get('/budgets', BudgetsController::class)->name('budgets.index');
    Route::get('/suppliers', SuppliersController::class)->name('suppliers.index');
    Route::get('/products', ProductsController::class)->name('products.index');
    Route::get('/products/{id}/show', function ($id) {
        $product = \App\Models\Product::where('id', $id)
            ->where('team_id', auth()->user()->team_id)
            ->firstOrFail();
        return response()->json($product);
    })->name('products.show');
    Route::get('/company-reasons', CompanyReasonsController::class)->name('company.reasons.index');
    Route::get('/seasons', SeasonsController::class)->name('seasons.index');
    Route::get('/users', UsersController::class)->name('users.index');
    Route::get('/machineries', MachineriesController::class)->name('machineries.index');
    Route::get('/type-machineries', TypeMachineriesController::class)->name('type.machineries.index');

    Route::post('/teams/store', StoreTeamController::class)->name('teams.store');
    Route::post('teams/{user}/update', UpdateTeamController::class)->name('teams.update');
    Route::delete('/teams/{user}/delete', DeleteTeamController::class)->name('teams.delete');
    Route::post('/teams/{user}/activate/inactivate', ActivateInactivateTeamController::class)->name('teams.activate.inactivate');

    Route::get('/users/pdf', UsersPdfController::class)->name('users.pdf');
    Route::get('/users/excel', UsersExcelController::class)->name('users.excel');
    Route::post('/users/store', StoreUserController::class)->name('users.store');
    Route::post('users/{user}/update', UpdateUserController::class)->name('users.update');
    Route::delete('/users/{user}/delete', DeleteUserController::class)->name('users.delete');
    Route::post('/users/{user}/activate/inactivate', ActiveInactiveUserController::class)->name('users.activate.inactivate');

    Route::get('/budgets/pdf', BudgetsPdfController::class)->name('budgets.pdf');
    Route::get('/budgets/excel', BudgetsExcelController::class)->name('budgets.excel');
    Route::post('/budgets/store', StoreBudgetController::class)->name('budgets.store');
    Route::post('/budgets/{budget}/update', UpdateBudgetController::class)->name('budgets.update');
    Route::delete('/budgets/{budget}/delete', DeleteBudgetController::class)->name('budgets.delete');

    Route::get('/suppliers/pdf', SuppliersPdfController::class)->name('suppliers.pdf');
    Route::get('/suppliers/excel', SuppliersExcelController::class)->name('suppliers.excel');
    Route::post('/suppliers/store', StoreSupplierController::class)->name('suppliers.store');
    Route::post('/suppliers/{supplier}/update', UpdateSupplierController::class)->name('suppliers.update');
    Route::delete('/suppliers/{supplier}/delete', DeleteSupplierController::class)->name('suppliers.delete');

    Route::get('/products/pdf', ProductsPdfController::class)->name('products.pdf');
    Route::get('/products/excel', ProductsExcelController::class)->name('products.excel');
    Route::post('/products/store', StoreProductController::class)->name('products.store');
    Route::post('/products/{product}/update', UpdateProductController::class)->name('products.update');
    Route::delete('/products/{product}/delete', DeleteProductController::class)->name('products.delete');

    Route::get('/company-reasons/pdf', CompanyReasonsPdfController::class)->name('company.reasons.pdf');
    Route::get('/company-reasons/excel', CompanyReasonsExcelController::class)->name('company.reasons.excel');
    Route::post('/company-reasons/store', StoreCompanyReasonController::class)->name('company.reasons.store');
    Route::post('/company-reasons/{companyReason}/update', UpdateCompanyReasonController::class)->name('company.reasons.update');
    Route::delete('/company-reasons/{companyReason}/delete', DeleteCompanyReasonController::class)->name('company.reasons.delete');

    Route::get('/invoices/pdf', InvoicesPdfController::class)->name('invoices.pdf');
    Route::get('/invoices/excel', InvoicesExcelController::class)->name('invoices.excel');
    Route::get('/invoices', InvoicesController::class)->name('invoices.index');
    Route::get('/invoices/{invoice}/show', ShowInvoiceController::class)->name('invoices.show');
    Route::get('/invoices/create', CreateInvoiceController::class)->name('invoices.create');
    Route::post('/invoices/extract-from-pdf', ExtractInvoiceFromPdfController::class)->name('invoices.extract');
    Route::post('/invoices/store', StoreInvoiceController::class)->name('invoices.store');
    Route::get('/invoices/{invoice}/edit', EditInvoiceController::class)->name('invoices.edit');
    Route::post('/invoices/{invoice}/update', UpdateInvoiceController::class)->name('invoices.update');
    Route::delete('/invoices/{invoice}/delete', DeleteInvoiceController::class)->name('invoices.delete');
    Route::get('/invoices/{invoice}/duplicate', DuplicateInvoiceController::class)->name('invoices.duplicate');


    Route::get('/credit-debit-notes', CreditDebitNotesController::class)->name('credit_debit_notes.index');
    Route::get('/credit-debit-notes/{note}/show', ShowCreditDebitNoteController::class)->name('credit_debit_notes.show');
    Route::get('/credit-debit-notes/create', CreateCreditDebitNoteController::class)->name('credit_debit_notes.create');
    Route::post('/credit-debit-notes/store', StoreCreditDebitNoteController::class)->name('credit_debit_notes.store');
    Route::get('/credit-debit-notes/{note}/edit', EditCreditDebitNoteController::class)->name('credit_debit_notes.edit');
    Route::post('/credit-debit-notes/{note}/update', UpdateCreditDebitNoteController::class)->name('credit_debit_notes.update');
    Route::delete('/credit-debit-notes/{note}/delete', DeleteCreditDebitNoteController::class)->name('credit_debit_notes.delete');




    


    Route::get('/levels2/{level1}/get', GetLevel2Controller::class)->name('levels2.get');
    Route::get('/levels3/{level2}/get', GetLevel3Controller::class)->name('levels3.get');
    Route::get('/levels4/{level3}/get', GetLevel4Controller::class)->name('levels4.get');

    Route::get('/levels', LevelsController::class)->name('levels.index');
    Route::get('/levels/pdf', LevelsPdfController::class)->name('levels.pdf');
    Route::get('/levels/excel', LevelsExcelController::class)->name('levels.excel');
    Route::post('/levels/store', StoreLevelController::class)->name('levels.store');
    Route::post('/levels/{level}/update', UpdateLevelController::class)->name('levels.update');
    Route::delete('/levels/{level}/delete', DeleteLevelController::class)->name('levels.delete');

    Route::get('/level2/{level1}/index', Level2Controller::class)->name('level2.index');
    Route::get('/level2/{level1}/pdf', Levels2PdfController::class)->name('levels2.pdf');
    Route::get('/level2/{level1}/excel', Levels2ExcelController::class)->name('levels2.excel');
    Route::post('/level2/store', StoreLevel2Controller::class)->name('level2.store');
    Route::post('/level2/{level2}/update', UpdateLevel2Controller::class)->name('level2.update');
    Route::delete('/level2/{level2}/delete', DeleteLevel2Controller::class)->name('level2.delete');

    Route::get('/level3/{level2}/index', Level3Controller::class)->name('level3.index');
    Route::get('/level3/{level2}/pdf', Levels3PdfController::class)->name('levels3.pdf');
    Route::get('/level3/{level2}/excel', Levels3ExcelController::class)->name('levels3.excel');
    Route::get('/level3/{level2}/import', ImportLevel3Controller::class)->name('levels3.import');
    Route::post('/level3/store', StoreLevel3Controller::class)->name('level3.store');
    Route::post('/level3/{level3}/update', UpdateLevel3Controller::class)->name('level3.update');
    Route::delete('/level3/{level3}/delete', DeleteLevel3Controller::class)->name('level3.delete');

    Route::get('/level4/{level3}/index', Level4Controller::class)->name('level4.index');
    Route::get('/level4/{level3}/pdf', Levels4PdfController::class)->name('levels4.pdf');
    Route::get('/level4/{level3}/excel', Levels4ExcelController::class)->name('levels4.excel');
    Route::post('/level4/store', StoreLevel4Controller::class)->name('level4.store');
    Route::post('/level4/{level4}/update', UpdateLevel4Controller::class)->name('level4.update');
    Route::delete('/level4/{level4}/delete', DeleteLevel4Controller::class)->name('level4.delete');

    Route::get('/fruits/pdf', FruitsPdfController::class)->name('fruits.pdf');
    Route::get('/fruits/excel', FruitsExcelController::class)->name('fruits.excel');
    Route::get('/fruits', FruitsController::class)->name('fruits.index');
    Route::post('/fruits/store', StoreFruitController::class)->name('fruits.store');
    Route::post('/fruits/{fruit}/update', UpdateFruitController::class)->name('fruits.update');
    Route::delete('/fruits/{fruit}/delete', DeleteFruitController::class)->name('fruits.delete');

    Route::get('/phenological-stages', PhenologicalStagesController::class)->name('phenological-stages.index');
    Route::post('/phenological-stages/store', StorePhenologicalStageController::class)->name('phenological-stages.store');
    Route::post('/phenological-stages/{phenologicalStage}/update', UpdatePhenologicalStageController::class)->name('phenological-stages.update');
    Route::delete('/phenological-stages/{phenologicalStage}/delete', DeletePhenologicalStageController::class)->name('phenological-stages.delete');

    Route::get('/varieties/pdf', VarietiesPdfController::class)->name('varieties.pdf');
    Route::get('/varieties/excel', VarietiesExcelController::class)->name('varieties.excel');
    Route::get('/varieties', VarietiesController::class)->name('varieties.index');
    Route::get('/varieties/{fruit}/get', GetVarietyController::class)->name('varieties.get');
    Route::post('/varieties/store', StoreVarietyController::class)->name('varieties.store');
    Route::post('/varieties/{variety}/update', UpdateVarietyController::class)->name('varieties.update');
    Route::delete('/varieties/{variety}/delete', DeleteVarietyController::class)->name('varieties.delete');

    Route::get('/rootstocks', RootstockController::class)->name('rootstocks.index');
    Route::post('/rootstocks/store', StoreRootstockController::class)->name('rootstocks.store');
    Route::post('/rootstocks/{rootstock}/update', UpdateRootstockController::class)->name('rootstocks.update');
    Route::delete('/rootstocks/{rootstock}/delete', DeleteRootstockController::class)->name('rootstocks.delete');

    Route::get('/cost-center-varieties', CostCenterVarietyController::class)->name('cost-center-varieties.index');
    Route::post('/cost-center-varieties/store', StoreCostCenterVarietyController::class)->name('cost-center-varieties.store');
    Route::post('/cost-center-varieties/{costCenterVariety}/update', UpdateCostCenterVarietyController::class)->name('cost-center-varieties.update');
    Route::delete('/cost-center-varieties/{costCenterVariety}/delete', DeleteCostCenterVarietyController::class)->name('cost-center-varieties.delete');

    Route::get('/parcels/pdf', ParcelsPdfController::class)->name('parcels.pdf');
    Route::get('/parcels/excel', ParcelsExcelController::class)->name('parcels.excel');
    Route::get('/parcels', ParcelsController::class)->name('parcels.index');
    Route::post('/parcels/store', StoreParcelController::class)->name('parcels.store');
    Route::post('/parcels/{parcel}/update', UpdateParcelController::class)->name('parcels.update');
    Route::delete('/parcels/{parcel}/delete', DeleteParcelController::class)->name('parcels.delete');
    Route::post('/parcels/transfer', TransferParcelsController::class)->name('parcels.transfer');
    Route::get('/parcels/previous-season', [ParcelsController::class, 'previousSeason'])->name('parcels.previous-season');

    Route::get('/seasons/pdf', SeasonsPdfController::class)->name('seasons.pdf');
    Route::get('/seasons/excel', SeasonsExcelController::class)->name('seasons.excel');
    Route::post('/seasons/store', StoreSeasonController::class)->name('seasons.store');
    Route::post('/seasons/{season}/update', UpdateSeasonController::class)->name('seasons.update');
    Route::delete('/seasons/{season}/delete', DeleteSeasonController::class)->name('seasons.delete');
    Route::post('/seasons/{season}/set-default', SetDefaultSeasonController::class)->name('seasons.set-default');
    Route::post('/seasons/{season}/copy-budget', CopyBudgetController::class)->name('seasons.copy-budget');

    // Guía del Sistema (fuera de check.selected.budget porque no requiere temporada)
    Route::get('/system-guide', SystemGuideController::class)->name('system-guide');

    Route::middleware(['check.selected.budget'])->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/home', HomeController::class)->name('home.index');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/technicalpanel', TechnicalPanelController::class)->name('technicalpanel');

    Route::get('/agrochemicals', AgrochemicalsController::class)->name('agrochemicals.index');

    // Inversiones
    Route::get('/investments', IndexInvestmentController::class)->name('investments.index');
    Route::post('/investments/store', StoreInvestmentController::class)->name('investments.store');
    Route::post('/investments/{investment}/update', UpdateInvestmentController::class)->name('investments.update');
    Route::delete('/investments/{investment}', DeleteInvestmentController::class)->name('investments.delete');

    // Proyectos
    Route::get('/projects', IndexProjectController::class)->name('projects.index');
    Route::post('/projects/store', StoreProjectController::class)->name('projects.store');
    Route::post('/projects/{project}/update', UpdateProjectController::class)->name('projects.update');
    Route::delete('/projects/{project}', DeleteProjectController::class)->name('projects.delete');
        Route::get('/fertilizers', FertilizersController::class)->name('fertilizers.index');
        Route::get('/cost-centers', CostCentersController::class)->name('cost.centers.index');
        Route::get('/manpowers', ManPowersController::class)->name('manpowers.index');
        Route::get('/supplies', SuppliesController::class)->name('supplies.index');
        Route::get('/services', ServicesController::class)->name('services.index');
        Route::get('/administrations', AdministrationsController::class)->name('administrations.index');
        Route::get('/fields', FieldsController::class)->name('fields.index');
        Route::get('/harvests', HarvestsController::class)->name('harvests.index');
        Route::get('/estimates', EstimatesController::class)->name('estimates.index');
        Route::get('/groupings', GroupingsController::class)->name('groupings.index');

        Route::get('/cost-centers/pdf', CostCentersPdfController::class)->name('cost.centers.pdf');
        Route::get('/cost-centers/excel', CostCentersExcelController::class)->name('cost.centers.excel');
        Route::post('/cost-centers/store', StoreCostCenterController::class)->name('cost.centers.store');
        Route::post('/cost-centers/{costCenter}/update', UpdateCostCenterController::class)->name('cost.centers.update');
        Route::delete('/cost-centers/{costCenter}/delete', DeleteCostCenterController::class)->name('cost.centers.delete');
        Route::post('/cost-centers/import', [CostCentersController::class, 'import'])->name('cost.centers.import');
        Route::get('/cost-centers/template', [CostCentersController::class, 'template'])->name('cost.centers.template');
        Route::post('/cost-centers/copy', CopyCostCentersController::class)->name('cost.centers.copy');

        Route::post('/agrochemicals/store', StoreAgrochemicalController::class)->name('agrochemicals.store');
        Route::post('/agrochemicals/{agrochemical}/update', UpdateAgrochemicalController::class)->name('agrochemicals.update');
        Route::delete('/agrochemicals/{agrochemical}/delete', DeleteAgrochemicalController::class)->name('agrochemicals.delete');


        Route::post('/fertilizers/store', StoreFertilizerController::class)->name('fertilizers.store');
        Route::post('/fertilizers/{fertilizer}/update', UpdateFertilizerController::class)->name('fertilizers.update');
        Route::delete('/fertilizers/{fertilizer}/delete', DeleteFertilizerController::class)->name('fertilizers.delete');


        Route::post('/harvests/store', StoreHarvestController::class)->name('harvests.store');
        Route::post('/harvests/{harvest}/update', UpdateHarvestController::class)->name('harvests.update');
        Route::delete('/harvests/{harvest}/delete', DeleteHarvestController::class)->name('harvests.delete');



        Route::post('/groupings/store', StoreGroupingController::class)->name('groupings.store');
        Route::post('/groupings/{grouping}/update', UpdateGroupingController::class)->name('groupings.update');
        Route::delete('/groupings/{grouping}/delete', DeleteGroupingController::class)->name('groupings.delete');



        Route::post('/man-powers/store', StoreManPowerController::class)->name('man.powers.store');
        Route::post('/man-powers/{manPower}/update', UpdateManPowerController::class)->name('man.powers.update');
        Route::delete('/man-powers/{manPower}/delete', DeleteManPowerController::class)->name('man.powers.delete');

        Route::get('/machineries/pdf', MachineriesPdfController::class)->name('machineries.pdf');
        Route::get('/machineries/excel', MachineriesExcelController::class)->name('machineries.excel');
        Route::post('/machineries/store', StoreMachineryController::class)->name('machineries.store');
        Route::post('/machineries/{machinery}/update', UpdateMachineryController::class)->name('machineries.update');
        Route::delete('/machineries/{machinery}/delete', DeleteMachineryController::class)->name('machineries.delete');

        Route::get('/type-machineries/pdf', TypeMachineriesPdfController::class)->name('type.machineries.pdf');
        Route::get('/type-machineries/excel', TypeMachineriesExcelController::class)->name('type.machineries.excel');
        Route::post('/type-machineries/store', StoreTypeMachineryController::class)->name('type.machineries.store');
        Route::post('/type-machineries/{typeMachinery}/update', UpdateTypeMachineryController::class)->name('type.machineries.update');
        Route::delete('/type-machineries/{typeMachinery}/delete', DeleteTypeMachineryController::class)->name('type.machineries.delete');

        Route::post('/supplies/store', StoreSupplyController::class)->name('supplies.store');
        Route::post('/supplies/{supply}/update', UpdateSupplyController::class)->name('supplies.update');
        Route::delete('/supplies/{supply}/delete', DeleteSupplyController::class)->name('supplies.delete');

        Route::post('/services/store', StoreServiceController::class)->name('services.store');
        Route::post('/services/{service}/update', UpdateServiceController::class)->name('services.update');
        Route::delete('/services/{service}/delete', DeleteServiceController::class)->name('services.delete');

        Route::post('/estimates/store', StoreEstimateController::class)->name('estimates.store');
        Route::post('/estimates/{estimate}/update', UpdateEstimateController::class)->name('estimates.update');
        Route::delete('/estimates/{estimate}/delete', DeleteEstimateController::class)->name('estimates.delete');

        Route::post('/administrations/store', StoreAdministrationController::class)->name('administrations.store');
        Route::post('/administrations/{administration}/update', UpdateAdministrationController::class)->name('administrations.update');
        Route::delete('/administrations/{administration}/delete', DeleteAdministrationController::class)->name('administrations.delete');

        Route::post('/fields/store', StoreFieldController::class)->name('fields.store');
        Route::post('/fields/{field}/update', UpdateFieldController::class)->name('fields.update');
        Route::delete('/fields/{field}/delete', DeleteFieldController::class)->name('fields.delete');
    });
    Route::get('/select-budge', SelectBudgetController::class)->name('select.budget');
    Route::post('/select-season/save', SaveSeasonController::class)->name('select.seasons.save');

    Route::get('/weather', [WeatherController::class, 'show'])->name('weather');

    // Summary de niveles anidados
    Route::get('/levels/summary', [LevelsController::class, 'summary'])->name('levels.summary');

    // Rutas para Outflows
    Route::get('/outflows', OutflowsController::class)->name('outflows.index');
    Route::get('/outflows/create', CreateOutflowController::class)->name('outflows.create');
    Route::post('/outflows', StoreOutflowController::class)->name('outflows.store');
    Route::get('/outflows/{outflow}', ShowOutflowController::class)->name('outflows.show');
    Route::get('/outflows/{outflow}/edit', EditOutflowController::class)->name('outflows.edit');
    Route::put('/outflows/{outflow}', UpdateOutflowController::class)->name('outflows.update');
    Route::delete('/outflows/{outflow}', DeleteOutflowController::class)->name('outflows.delete');
    Route::get('/outflows/level3-suggestions', GetLevel3SuggestionsController::class)->name('outflows.level3-suggestions');

    // Dashboard de Outflows
    Route::get('/outflows-dashboard', [OutflowsDashboardController::class, 'index'])->name('outflows.dashboard');
    
    // Dashboard Comparativo (Presupuesto vs Real)
    Route::get('/comparative-dashboard', [ComparativeOutflowsDashboardController::class, 'index'])->name('comparative.dashboard');

    // Dashboard Gestión por Hectárea
    Route::get('/hectare-dashboard', [HectareDashboardController::class, 'index'])->name('hectare.dashboard');
    
    // Consolidado de Outflows
    Route::get('/consolidated-outflows', [ConsolidatedOutflowsController::class, 'index'])->name('consolidated-outflows.index');
    Route::get('/consolidated-outflows/export', [ConsolidatedOutflowsController::class, 'export'])->name('consolidated-outflows.export');

    // Inventario
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

    Route::get('/kardex/{product}', [KardexController::class, 'show'])->name('kardex.show');

    Route::get('/product-stock-lines', [ProductStockLinesController::class, 'index'])->name('product-stock-lines.index');

   

    Route::get('/fuel-outflows', [FuelOutflowController::class, 'index'])->name('fuel-outflows.index');
    Route::get('/fuel-outflows/analytics', [FuelOutflowController::class, 'analytics'])->name('fuel-outflows.analytics');
    Route::post('/fuel-outflows', StoreFuelOutflowController::class)->name('fuel-outflows.store');
    Route::get('/fuel-outflows/{fuelOutFlow}', ShowFuelOutflowController::class)->name('fuel-outflows.show');
    Route::get('/fuel-outflows/{fuelOutFlow}/edit', EditFuelOutflowController::class)->name('fuel-outflows.edit');
    Route::put('/fuel-outflows/{fuelOutFlow}', UpdateFuelOutflowController::class)->name('fuel-outflows.update');
    Route::delete('/fuel-outflows/{fuelOutFlow}', DeleteFuelOutflowController::class)->name('fuel-outflows.delete');

    // Operators
    Route::get('/operators', OperatorsController::class)->name('operators.index');
    Route::post('/operators/store', StoreOperatorController::class)->name('operators.store');
    Route::post('/operators/{operator}/update', UpdateOperatorController::class)->name('operators.update');
    Route::delete('/operators/{operator}/delete', DeleteOperatorController::class)->name('operators.delete');

    // Application Orders
    Route::get('/application-orders', [ApplicationOrdersController::class, 'index'])->name('application-orders.index');
    Route::post('/application-orders', StoreApplicationOrderController::class)->name('application-orders.store');
    Route::get('/application-orders/{applicationOrder}/pdf', PdfApplicationOrderController::class)->name('application-orders.pdf');
    Route::get('/application-orders/{applicationOrder}', ShowApplicationOrderController::class)->name('application-orders.show');
    Route::put('/application-orders/{applicationOrder}', UpdateApplicationOrderController::class)->name('application-orders.update');
    Route::delete('/application-orders/{applicationOrder}', DeleteApplicationOrderController::class)->name('application-orders.delete');

    // Irrigation Pumps
    Route::get('/irrigation-pumps', [IrrigationPumpsController::class, 'index'])->name('irrigation-pumps.index');
    Route::post('/irrigation-pumps', StoreIrrigationPumpController::class)->name('irrigation-pumps.store');
    Route::put('/irrigation-pumps/{irrigationPump}', UpdateIrrigationPumpController::class)->name('irrigation-pumps.update');
    Route::delete('/irrigation-pumps/{irrigationPump}', DeleteIrrigationPumpController::class)->name('irrigation-pumps.delete');

    // Fertilizer Orders
    Route::get('/fertilizer-orders', [FertilizerOrdersController::class, 'index'])->name('fertilizer-orders.index');
    Route::post('/fertilizer-orders', StoreFertilizerOrderController::class)->name('fertilizer-orders.store');
    Route::get('/fertilizer-orders/{fertilizerOrder}/pdf', PdfFertilizerOrderController::class)->name('fertilizer-orders.pdf');
    Route::get('/fertilizer-orders/{fertilizerOrder}', ShowFertilizerOrderController::class)->name('fertilizer-orders.show');
    Route::put('/fertilizer-orders/{fertilizerOrder}', UpdateFertilizerOrderController::class)->name('fertilizer-orders.update');
    Route::delete('/fertilizer-orders/{fertilizerOrder}', DeleteFertilizerOrderController::class)->name('fertilizer-orders.delete');

    // Agrochemical Outflows
    Route::get('/agrochemical-outflows', [AgrochemicalOutflowController::class, 'index'])->name('agrochemical-outflows.index');
    Route::post('/agrochemical-outflows', StoreAgrochemicalOutflowController::class)->name('agrochemical-outflows.store');
    Route::put('/agrochemical-outflows', UpdateAgrochemicalOutflowController::class)->name('agrochemical-outflows.update');
    Route::delete('/agrochemical-outflows/{agrochemicalOutflow}', DeleteAgrochemicalOutflowController::class)->name('agrochemical-outflows.delete');
    Route::delete('/agrochemical-outflows/{applicationOrder}/revert', RevertAgrochemicalOutflowController::class)->name('agrochemical-outflows.revert');

    // Libro de Campo
    Route::get('/libro-campo', [LibroCampoController::class, 'index'])->name('libro-campo.index');
    Route::get('/libro-campo/export-pdf', [LibroCampoController::class, 'exportPdf'])->name('libro-campo.export-pdf');

    // Fertilizer Outflows
    Route::get('/fertilizer-outflows', [FertilizerOutflowController::class, 'index'])->name('fertilizer-outflows.index');
    Route::post('/fertilizer-outflows', StoreFertilizerOutflowController::class)->name('fertilizer-outflows.store');
    Route::get('/fertilizer-outflows/{fertilizerOutflow}/edit', EditFertilizerOutflowController::class)->name('fertilizer-outflows.edit');
    Route::put('/fertilizer-outflows/{fertilizerOutflow}', UpdateFertilizerOutflowController::class)->name('fertilizer-outflows.update');
    Route::delete('/fertilizer-outflows/{fertilizerOutflow}', DeleteFertilizerOutflowController::class)->name('fertilizer-outflows.delete');

    // Invoice Payments
    Route::get('/invoice-payments', [InvoicePaymentController::class, 'index'])->name('invoice-payments.index');
    Route::get('/invoice-payments/dashboard', [InvoicePaymentDashboardController::class, 'index'])->name('invoice-payments.dashboard');
    Route::get('/invoice-payments/excel', InvoicePaymentsExcelController::class)->name('invoice-payments.excel');
    Route::get('/api/invoices/search', [InvoicePaymentController::class, 'searchInvoices'])->name('invoices.search');
    Route::post('/invoice-payments', StoreInvoicePaymentController::class)->name('invoice-payments.store');
    Route::put('/invoice-payments/{payment}', UpdateInvoicePaymentController::class)->name('invoice-payments.update');
    Route::delete('/invoice-payments/{payment}', DeleteInvoicePaymentController::class)->name('invoice-payments.delete');

    // Rendiciones de Gastos
    Route::get('/expense-reports', [ExpenseReportController::class, 'index'])->name('expense-reports.index');
    Route::post('/expense-reports', StoreExpenseReportController::class)->name('expense-reports.store');
    Route::get('/expense-reports/{expenseReport}', ShowExpenseReportController::class)->name('expense-reports.show');
    Route::delete('/expense-reports/{expenseReport}', DeleteExpenseReportController::class)->name('expense-reports.delete');
    Route::patch('/expense-reports/{expenseReport}/status', UpdateExpenseReportStatusController::class)->name('expense-reports.update-status');
    Route::post('/expense-reports/{expenseReport}/items', StoreExpenseReportItemController::class)->name('expense-reports.items.store');
    Route::delete('/expense-report-items/{item}', DeleteExpenseReportItemController::class)->name('expense-reports.items.delete');
    Route::get('/expense-reports-export', ExportExpenseReportsController::class)->name('expense-reports.export');

    // Purchase Orders
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::post('/purchase-orders', StorePurchaseOrderController::class)->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', ShowPurchaseOrderController::class)->name('purchase-orders.show');
    Route::put('/purchase-orders/{purchaseOrder}', UpdatePurchaseOrderController::class)->name('purchase-orders.update');
    Route::delete('/purchase-orders/{purchaseOrder}', DeletePurchaseOrderController::class)->name('purchase-orders.delete');
    Route::patch('/purchase-orders/{purchaseOrder}/status', UpdatePurchaseOrderStatusController::class)->name('purchase-orders.update-status');

    // Consolidated Documents
    Route::get('/consolidated-documents', [ConsolidatedDocumentsController::class, 'index'])->name('consolidated-documents.index');

    // Production Dispatches (Despachos de Producción)
    Route::get('/production-dispatches', [ProductionDispatchController::class, 'index'])->name('production-dispatches.index');
    Route::post('/production-dispatches', StoreProductionDispatchController::class)->name('production-dispatches.store');
    Route::put('/production-dispatches/{productionDispatch}', UpdateProductionDispatchController::class)->name('production-dispatches.update');
    Route::delete('/production-dispatches/{productionDispatch}', DeleteProductionDispatchController::class)->name('production-dispatches.delete');
    Route::put('/production-dispatches/{productionDispatch}/process', ProcessProductionDispatchController::class)->name('production-dispatches.process');

    // Production Summaries (Resumen de Producción)
    Route::get('/production-summaries', ProductionSummaryController::class)->name('production-summaries.index');
    Route::post('/production-summaries', StoreProductionSummaryController::class)->name('production-summaries.store');
    Route::post('/production-summaries/{id}/update', UpdateProductionSummaryController::class)->name('production-summaries.update');
    Route::delete('/production-summaries/{id}/delete', DeleteProductionSummaryController::class)->name('production-summaries.delete');

    // Exporters (Exportadoras)
    Route::post('/exporters', StoreExporterController::class)->name('exporters.store');
    Route::put('/exporters/{exporter}', UpdateExporterController::class)->name('exporters.update');
    Route::delete('/exporters/{exporter}', DeleteExporterController::class)->name('exporters.delete');

    // Packing Houses
    Route::post('/packing-houses', StorePackingHouseController::class)->name('packing-houses.store');
    Route::put('/packing-houses/{packingHouse}', UpdatePackingHouseController::class)->name('packing-houses.update');
    Route::delete('/packing-houses/{packingHouse}', DeletePackingHouseController::class)->name('packing-houses.delete');

    // Bin Types (Tipos de Bins)
    Route::post('/bin-types', StoreBinTypeController::class)->name('bin-types.store');
    Route::put('/bin-types/{binType}', UpdateBinTypeController::class)->name('bin-types.update');
    Route::delete('/bin-types/{binType}', DeleteBinTypeController::class)->name('bin-types.delete');

    // Box Types (Tipos de Cajas)
    Route::post('/box-types', StoreBoxTypeController::class)->name('box-types.store');
    Route::put('/box-types/{boxType}', UpdateBoxTypeController::class)->name('box-types.update');
    Route::delete('/box-types/{boxType}', DeleteBoxTypeController::class)->name('box-types.delete');

    // Carriers (Transportistas)
    Route::post('/carriers', StoreCarrierController::class)->name('carriers.store');
    Route::put('/carriers/{carrier}', UpdateCarrierController::class)->name('carriers.update');
    Route::delete('/carriers/{carrier}', DeleteCarrierController::class)->name('carriers.delete');

    // API - Refrescar selects de producción
    Route::get('/api/exporters', GetExportersController::class)->name('api.exporters');
    Route::get('/api/packing-houses', GetPackingHousesController::class)->name('api.packing-houses');
    Route::get('/api/bin-types', GetBinTypesController::class)->name('api.bin-types');
    Route::get('/api/box-types', GetBoxTypesController::class)->name('api.box-types');
    Route::get('/api/carriers', GetCarriersController::class)->name('api.carriers');
    // Employees (Colaboradores - Remuneraciones)
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', StoreEmployeeController::class)->name('employees.store');
    Route::put('/employees/{employee}', UpdateEmployeeController::class)->name('employees.update');
    Route::delete('/employees/{employee}', DeleteEmployeeController::class)->name('employees.delete');
    Route::post('/employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('/employees/template', [EmployeeController::class, 'template'])->name('employees.template');

    // Contracts (Contratos - Remuneraciones)
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::post('/contracts', StoreContractController::class)->name('contracts.store');
    Route::put('/contracts/{contract}', UpdateContractController::class)->name('contracts.update');
    Route::delete('/contracts/{contract}', DeleteContractController::class)->name('contracts.delete');

    // Labor Types (Tipos de Labor - Remuneraciones)
    Route::get('/labor-types', [LaborTypeController::class, 'index'])->name('labor-types.index');
    Route::post('/labor-types', StoreLaborTypeController::class)->name('labor-types.store');
    Route::put('/labor-types/{laborType}', UpdateLaborTypeController::class)->name('labor-types.update');
    Route::delete('/labor-types/{laborType}', DeleteLaborTypeController::class)->name('labor-types.delete');
    Route::get('/labor-types/export-pdf', ExportLaborTypesPdfController::class)->name('labor-types.export-pdf');
    Route::get('/labor-types/catalog', ShowLaborTypesCatalogController::class)->name('labor-types.show');

    // Bonus Types (Tipos de Bono - Remuneraciones)
    Route::get('/bonus-types', [BonusTypeController::class, 'index'])->name('bonus-types.index');
    Route::post('/bonus-types', StoreBonusTypeController::class)->name('bonus-types.store');
    Route::put('/bonus-types/{bonusType}', UpdateBonusTypeController::class)->name('bonus-types.update');
    Route::delete('/bonus-types/{bonusType}', DeleteBonusTypeController::class)->name('bonus-types.delete');

    // Labor Rates (Tarifas de Labor - Remuneraciones)
    Route::get('/labor-rates', [LaborRateController::class, 'index'])->name('labor-rates.index');
    Route::post('/labor-rates', StoreLaborRateController::class)->name('labor-rates.store');
    Route::put('/labor-rates/{laborRate}', UpdateLaborRateController::class)->name('labor-rates.update');
    Route::delete('/labor-rates/{laborRate}', DeleteLaborRateController::class)->name('labor-rates.delete');
    Route::get('/labor-rates/export-pdf', ExportLaborRatesPdfController::class)->name('labor-rates.export-pdf');
    Route::get('/labor-rates/catalog', ShowLaborRatesCatalogController::class)->name('labor-rates.show');

    // Daily Attendances (Asistencia Diaria - Remuneraciones)
    Route::get('/daily-attendances', [DailyAttendanceController::class, 'index'])->name('daily-attendances.index');
    Route::post('/daily-attendances', StoreDailyAttendanceController::class)->name('daily-attendances.store');
    Route::delete('/daily-attendances', DeleteDailyAttendanceController::class)->name('daily-attendances.delete');

    // Daily Yields (Tarjas Diarias - Remuneraciones)
    Route::get('/daily-yields', [DailyYieldController::class, 'index'])->name('daily-yields.index');
    Route::post('/daily-yields', StoreDailyYieldController::class)->name('daily-yields.store');
    Route::put('/daily-yields/{dailyYield}', UpdateDailyYieldController::class)->name('daily-yields.update');
    Route::delete('/daily-yields/{dailyYield}', DeleteDailyYieldController::class)->name('daily-yields.delete');

    // Work Schedules (Horario de Trabajo - Remuneraciones)
    Route::get('/work-schedules', [WorkScheduleController::class, 'index'])->name('work-schedules.index');
    Route::put('/work-schedules', UpdateWorkScheduleController::class)->name('work-schedules.update');

    // Daily Management (Gestión Diaria - Hub consolidado)
    Route::get('/daily-management', [DailyManagementController::class, 'index'])->name('daily-management.index');
    Route::get('/daily-management/monthly-report', MonthlyReportController::class)->name('daily-management.monthly-report');
    Route::get('/daily-management/export-excel', ExportMonthlyExcelController::class)->name('daily-management.export-excel');
    Route::get('/daily-management/export-pdf', ExportMonthlyPdfController::class)->name('daily-management.export-pdf');

    // Project Evaluations (Evaluación de Proyectos Agrícolas)
    Route::get('/project-evaluations', [ProjectEvaluationController::class, 'index'])->name('project-evaluations.index');
    Route::post('/project-evaluations', StoreProjectEvaluationController::class)->name('project-evaluations.store');
    Route::put('/project-evaluations/{projectEvaluation}', UpdateProjectEvaluationController::class)->name('project-evaluations.update');
    Route::delete('/project-evaluations/{projectEvaluation}', DeleteProjectEvaluationController::class)->name('project-evaluations.delete');
    Route::get('/project-evaluations/{projectEvaluation}', ShowProjectEvaluationController::class)->name('project-evaluations.show');
    // Filas de composición
    Route::post('/project-evaluations/{projectEvaluation}/rows', StoreProjectEvaluationRowController::class)->name('project-evaluations.rows.store');
    Route::post('/project-evaluations/{projectEvaluation}/rows/bulk', StoreBulkProjectEvaluationRowsController::class)->name('project-evaluations.rows.bulk-store');
    Route::put('/project-evaluations/{projectEvaluation}/rows/{row}', UpdateProjectEvaluationRowController::class)->name('project-evaluations.rows.update');
    Route::delete('/project-evaluations/{projectEvaluation}/rows/{row}', DeleteProjectEvaluationRowController::class)->name('project-evaluations.rows.delete');
    // Parámetros (a nivel de evaluación)
    Route::post('/project-evaluations/{projectEvaluation}/params/rnp-prices', UpsertRnpPricesController::class)->name('project-evaluations.rnp-prices.upsert');
    Route::post('/project-evaluations/{projectEvaluation}/params/variety-costs', UpsertVarietyCostParamsController::class)->name('project-evaluations.variety-costs.upsert');
    Route::post('/project-evaluations/{projectEvaluation}/params/kg-yield-costs', UpsertKgYieldCostsController::class)->name('project-evaluations.kg-yield-costs.upsert');

    // Contract Templates (Plantillas de Contrato)
    Route::get('/contract-templates', [ContractTemplateController::class, 'index'])->name('contract-templates.index');
    Route::post('/contract-templates', StoreContractTemplateController::class)->name('contract-templates.store');
    Route::delete('/contract-templates/{contractTemplate}', DeleteContractTemplateController::class)->name('contract-templates.delete');
    Route::post('/contract-templates/{contractTemplate}/generate', GenerateContractController::class)->name('contract-templates.generate');

    // API Schedules (Horarios)
    Route::get('/api/schedules', [ScheduleApiController::class, 'index'])->name('api.schedules');
    Route::post('/api/schedules', [ScheduleApiController::class, 'store'])->name('api.schedules.store');
    Route::delete('/api/schedules/{schedule}', [ScheduleApiController::class, 'destroy'])->name('api.schedules.delete');

    // API Cities (Ciudades)
    Route::get('/api/cities', [CityApiController::class, 'index'])->name('api.cities');
    Route::post('/api/cities', [CityApiController::class, 'store'])->name('api.cities.store');
    Route::delete('/api/cities/{city}', [CityApiController::class, 'destroy'])->name('api.cities.delete');

    Route::get('/api/fruit-classifications', [FruitClassificationController::class, 'index'])->name('api.fruit-classifications.index');
    Route::post('/api/fruit-classifications', [FruitClassificationController::class, 'store'])->name('api.fruit-classifications.store');
    Route::delete('/api/fruit-classifications/{fruitClassificationType}', [FruitClassificationController::class, 'destroy'])->name('api.fruit-classifications.delete');

});

// Rutas firmadas para aprobación/rechazo de Rendiciones de Gastos
Route::get('/expense-reports/{expenseReport}/approve', ApproveExpenseReportController::class)
    ->middleware('signed')
    ->name('expense-reports.approve');

Route::match(['get', 'post'], '/expense-reports/{expenseReport}/reject', RejectExpenseReportController::class)
    ->middleware('signed')
    ->name('expense-reports.reject');

// Rutas firmadas para aprobación/rechazo de Purchase Orders
// El controller valida autenticación, rol y team_id internamente
Route::get('/purchase-orders/{purchaseOrder}/approve', ApprovePurchaseOrderController::class)
    ->middleware('signed')
    ->name('purchase-orders.approve');

Route::match(['get', 'post'], '/purchase-orders/{purchaseOrder}/reject', RejectPurchaseOrderController::class)
    ->middleware('signed')
    ->name('purchase-orders.reject');

// Ruta de prueba para emails (SOLO DESARROLLO - eliminar en producción)
Route::get('/test-email/{purchaseOrder}', function(\App\Models\PurchaseOrder $purchaseOrder) {
    // Probar email de pending approval
    $approverName = auth()->user()?->name ?? 'Juan Pérez';
    $mailable = new \App\Mail\PurchaseOrderPendingApproval($purchaseOrder, $approverName);
    
    // Opción 1: Ver el HTML del email en el navegador
    return $mailable->render();
    
    // Opción 2: Enviar email de prueba (descomentar para usar)
    // \Illuminate\Support\Facades\Mail::to('test@example.com')->send($mailable);
    // return 'Email enviado! Revisa storage/logs/laravel.log';
})->name('test.email')->middleware('auth');

// Panel de testing de emails (SOLO DESARROLLO)
Route::get('/test-emails', \App\Http\Controllers\Testing\TestEmailController::class)
    ->name('test.emails')
    ->middleware('auth');

Route::get('/test-emails/preview/{type}', [\App\Http\Controllers\Testing\TestEmailController::class, 'preview'])
    ->name('test.email.preview')
    ->middleware('auth');

Route::get('/test-emails/send/{type}', [\App\Http\Controllers\Testing\TestEmailController::class, 'send'])
    ->name('test.email.send')
    ->middleware('auth');

// Prueba rápida de SMTP (SOLO DESARROLLO - eliminar en producción)
Route::get('/test-smtp', function() {
    try {
        \Illuminate\Support\Facades\Mail::raw('✅ Test email desde Laravel - SMTP funcionando correctamente', function($message) {
            $message->to('gestion@gestionagricola.cl')
                    ->subject('Test SMTP - Presupuesto');
        });
        return '<h1>✅ Email enviado exitosamente!</h1><p>Revisa tu bandeja de entrada en: gestion@gestionagricola.cl</p><p><strong>Nota:</strong> Si no llega, revisa la carpeta de SPAM.</p><p><a href="javascript:history.back()">← Volver</a></p>';
    } catch (\Exception $e) {
        return '<h1>❌ Error al enviar email</h1><pre style="background:#f8d7da;padding:20px;border-radius:5px;">' . $e->getMessage() . '</pre><hr><h3>Posibles soluciones:</h3><ul><li>Verificar que el host sea: <strong>mail.gestionagricola.cl</strong> o <strong>smtp.gestionagricola.cl</strong></li><li>Probar puerto 465 con SSL en lugar de 587 con TLS</li><li>Verificar usuario y contraseña</li></ul><p><a href="javascript:history.back()">← Volver</a></p>';
    }
})->middleware('auth');

// Diagnóstico completo de SMTP (SOLO DESARROLLO)
Route::get('/smtp-diagnostic', function() {
    $configs = [
        ['host' => 'mail.gestionagricola.cl', 'port' => 587, 'encryption' => 'tls'],
        ['host' => 'mail.gestionagricola.cl', 'port' => 465, 'encryption' => 'ssl'],
        ['host' => 'smtp.gestionagricola.cl', 'port' => 587, 'encryption' => 'tls'],
        ['host' => 'smtp.gestionagricola.cl', 'port' => 465, 'encryption' => 'ssl'],
        ['host' => 'mail.gestionagricola.cl', 'port' => 25, 'encryption' => null],
    ];

    $results = [];
    foreach ($configs as $config) {
        try {
            // Configurar temporalmente
            config([
                'mail.mailers.smtp.host' => $config['host'],
                'mail.mailers.smtp.port' => $config['port'],
                'mail.mailers.smtp.encryption' => $config['encryption'],
            ]);

            // Intentar enviar
            \Illuminate\Support\Facades\Mail::raw('Test', function($message) {
                $message->to('gestion@gestionagricola.cl')->subject('Test');
            });

            $results[] = [
                'config' => $config,
                'status' => 'success',
                'message' => 'Email enviado exitosamente'
            ];
            break; // Si uno funciona, salir
        } catch (\Exception $e) {
            $results[] = [
                'config' => $config,
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    // Mostrar resultados
    $html = '<h1>🔍 Diagnóstico SMTP</h1><style>
        body { font-family: Arial; padding: 20px; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
        code { background: #f8f9fa; padding: 2px 8px; border-radius: 3px; }
    </style>';

    foreach ($results as $result) {
        $class = $result['status'] === 'success' ? 'success' : 'error';
        $icon = $result['status'] === 'success' ? '✅' : '❌';
        
        $html .= "<div class='$class'>";
        $html .= "<strong>$icon Host:</strong> <code>{$result['config']['host']}</code> ";
        $html .= "<strong>Puerto:</strong> <code>{$result['config']['port']}</code> ";
        $html .= "<strong>Encryption:</strong> <code>" . ($result['config']['encryption'] ?: 'none') . "</code><br>";
        $html .= "<strong>Resultado:</strong> {$result['message']}";
        $html .= "</div>";
    }

    $html .= '<hr><p><a href="/test-smtp">← Volver a test simple</a></p>';
    
    return $html;
})->middleware('auth');
