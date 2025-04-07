<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgrochemicalsController;
use App\Http\Controllers\FertilizersController;
use App\Http\Controllers\TeamsController;
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
use App\Http\Controllers\CostCenters\StoreCostCenterController;
use App\Http\Controllers\CostCenters\UpdateCostCenterController;
use App\Http\Controllers\CostCenters\DeleteCostCenterController;
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
use App\Http\Controllers\Level4s\StoreLevel4Controller;
use App\Http\Controllers\Level4s\UpdateLevel4Controller; 
use App\Http\Controllers\Level4s\DeleteLevel4Controller;
use App\Http\Controllers\Fruits\StoreFruitController;
use App\Http\Controllers\Fruits\UpdateFruitController;
use App\Http\Controllers\Fruits\DeleteFruitController;
use App\Http\Controllers\Varieties\GetVarietyController;
use App\Http\Controllers\Varieties\StoreVarietyController;
use App\Http\Controllers\Varieties\UpdateVarietyController;
use App\Http\Controllers\Varieties\DeleteVarietyController;
use App\Http\Controllers\Parcels\StoreParcelController;
use App\Http\Controllers\Parcels\UpdateParcelController;
use App\Http\Controllers\Parcels\DeleteParcelController;
use App\Http\Controllers\Seasons\StoreSeasonController;
use App\Http\Controllers\Seasons\UpdateSeasonController;
use App\Http\Controllers\Seasons\DeleteSeasonController;
use App\Http\Controllers\Machineries\StoreMachineryController;
use App\Http\Controllers\Machineries\UpdateMachineryController;
use App\Http\Controllers\Machineries\DeleteMachineryController;
use App\Http\Controllers\TypeMachineries\StoreTypeMachineryController;
use App\Http\Controllers\TypeMachineries\UpdateTypeMachineryController;
use App\Http\Controllers\TypeMachineries\DeleteTypeMachineryController;
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

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/teams', TeamsController::class)->name('teams.index');
    Route::get('/budgets', BudgetsController::class)->name('budgets.index');
    Route::get('/suppliers', SuppliersController::class)->name('suppliers.index');
    Route::get('/products', ProductsController::class)->name('products.index');
    Route::get('/company-reasons', CompanyReasonsController::class)->name('company.reasons.index');
    Route::get('/seasons', SeasonsController::class)->name('seasons.index');
    Route::get('/users', UsersController::class)->name('users.index');
    Route::get('/machineries', MachineriesController::class)->name('machineries.index');
    Route::get('/type-machineries', TypeMachineriesController::class)->name('type.machineries.index');

    Route::post('/teams/store', StoreTeamController::class)->name('teams.store');
    Route::post('teams/{user}/update', UpdateTeamController::class)->name('teams.update');
    Route::delete('/teams/{user}/delete', DeleteTeamController::class)->name('teams.delete');
    Route::post('/teams/{user}/activate/inactivate', ActivateInactivateTeamController::class)->name('teams.activate.inactivate');

    Route::post('/users/store', StoreUserController::class)->name('users.store');
    Route::post('users/{user}/update', UpdateUserController::class)->name('users.update');
    Route::delete('/users/{user}/delete', DeleteUserController::class)->name('users.delete');
    Route::post('/users/{user}/activate/inactivate', ActiveInactiveUserController::class)->name('users.activate.inactivate');

    Route::post('/budgets/store', StoreBudgetController::class)->name('budgets.store');
    Route::post('/budgets/{budget}/update', UpdateBudgetController::class)->name('budgets.update');
    Route::delete('/budgets/{budget}/delete', DeleteBudgetController::class)->name('budgets.delete');

    Route::post('/suppliers/store', StoreSupplierController::class)->name('suppliers.store');
    Route::post('/suppliers/{supplier}/update', UpdateSupplierController::class)->name('suppliers.update');
    Route::delete('/suppliers/{supplier}/delete', DeleteSupplierController::class)->name('suppliers.delete');

     Route::post('/products/store', StoreProductController::class)->name('products.store');
    Route::post('/products/{product}/update', UpdateProductController::class)->name('products.update');
    Route::delete('/products/{product}/delete', DeleteProductController::class)->name('products.delete');

    Route::post('/company-reasons/store', StoreCompanyReasonController::class)->name('company.reasons.store');
    Route::post('/company-reasons/{companyReason}/update', UpdateCompanyReasonController::class)->name('company.reasons.update');
    Route::delete('/company-reasons/{companyReason}/delete', DeleteCompanyReasonController::class)->name('company.reasons.delete');

    Route::get('/invoices', InvoicesController::class)->name('invoices.index');
    Route::get('/invoices/{invoice}/show', ShowInvoiceController::class)->name('invoices.show');
    Route::get('/invoices/create', CreateInvoiceController::class)->name('invoices.create');
    Route::post('/invoices/store', StoreInvoiceController::class)->name('invoices.store');
    Route::get('/invoices/{invoice}/edit', EditInvoiceController::class)->name('invoices.edit'); 
    Route::post('/invoices/{invoice}/update', UpdateInvoiceController::class)->name('invoices.update'); 
    Route::delete('/invoices/{invoice}/delete', DeleteInvoiceController::class)->name('invoices.delete');

    Route::get('/levels2/{level1}/get', GetLevel2Controller::class)->name('levels2.get');
    Route::get('/levels3/{level2}/get', GetLevel3Controller::class)->name('levels3.get');
    Route::get('/levels4/{level3}/get', GetLevel4Controller::class)->name('levels4.get');

    Route::get('/levels', LevelsController::class)->name('levels.index');
    Route::post('/levels/store', StoreLevelController::class)->name('levels.store');
    Route::post('/levels/{level}/update', UpdateLevelController::class)->name('levels.update'); 
    Route::delete('/levels/{level}/delete', DeleteLevelController::class)->name('levels.delete');

    Route::get('/level2/{level1}/index', Level2Controller::class)->name('level2.index');
    Route::post('/level2/store', StoreLevel2Controller::class)->name('level2.store');
    Route::post('/level2/{level2}/update', UpdateLevel2Controller::class)->name('level2.update'); 
    Route::delete('/level2/{level2}/delete', DeleteLevel2Controller::class)->name('level2.delete');

    Route::get('/level3/{level2}/index', Level3Controller::class)->name('level3.index');
    Route::post('/level3/store', StoreLevel3Controller::class)->name('level3.store');
    Route::post('/level3/{level3}/update', UpdateLevel3Controller::class)->name('level3.update'); 
    Route::delete('/level3/{level3}/delete', DeleteLevel3Controller::class)->name('level3.delete');

    Route::get('/level4/{level3}/index', Level4Controller::class)->name('level4.index');
    Route::post('/level4/store', StoreLevel4Controller::class)->name('level4.store');
    Route::post('/level4/{level4}/update', UpdateLevel4Controller::class)->name('level4.update'); 
    Route::delete('/level4/{level4}/delete', DeleteLevel4Controller::class)->name('level4.delete');

    Route::get('/fruits', FruitsController::class)->name('fruits.index');
    Route::post('/fruits/store', StoreFruitController::class)->name('fruits.store');
    Route::post('/fruits/{fruit}/update', UpdateFruitController::class)->name('fruits.update');
    Route::delete('/fruits/{fruit}/delete', DeleteFruitController::class)->name('fruits.delete');

    Route::get('/varieties', VarietiesController::class)->name('varieties.index');
    Route::get('/varieties/{fruit}/get', GetVarietyController::class)->name('varieties.get');
    Route::post('/varieties/store', StoreVarietyController::class)->name('varieties.store');
    Route::post('/varieties/{variety}/update', UpdateVarietyController::class)->name('varieties.update');
     Route::delete('/varieties/{variety}/delete', DeleteVarietyController::class)->name('varieties.delete');


    Route::get('/parcels', ParcelsController::class)->name('parcels.index');
    Route::post('/parcels/store', StoreParcelController::class)->name('parcels.store');
    Route::post('/parcels/{parcel}/update', UpdateParcelController::class)->name('parcels.update');
     Route::delete('/parcels/{parcel}/delete', DeleteParcelController::class)->name('parcels.delete');

    Route::post('/seasons/store', StoreSeasonController::class)->name('seasons.store');
    Route::post('/seasons/{season}/update', UpdateSeasonController::class)->name('seasons.update');
    Route::delete('/seasons/{season}/delete', DeleteSeasonController::class)->name('seasons.delete');

    Route::middleware(['check.selected.budget'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/agrochemicals', AgrochemicalsController::class)->name('agrochemicals.index');
        Route::get('/fertilizers', FertilizersController::class)->name('fertilizers.index');
        Route::get('/cost-centers', CostCentersController::class)->name('cost.centers.index');
        Route::get('/manpowers', ManPowersController::class)->name('manpowers.index');

        Route::post('/cost-centers/store', StoreCostCenterController::class)->name('cost.centers.store');
        Route::post('/cost-centers/{costCenter}/update', UpdateCostCenterController::class)->name('cost.centers.update');
        Route::delete('/cost-centers/{costCenter}/delete', DeleteCostCenterController::class)->name('cost.centers.delete');

        Route::post('/agrochemicals/store', StoreAgrochemicalController::class)->name('agrochemicals.store');
        Route::post('/agrochemicals/{agrochemical}/update', UpdateAgrochemicalController::class)->name('agrochemicals.update');
        Route::delete('/agrochemicals/{agrochemical}/delete', DeleteAgrochemicalController::class)->name('agrochemicals.delete');
        
        Route::post('/fertilizers/store', StoreFertilizerController::class)->name('fertilizers.store');
        Route::post('/fertilizers/{fertilizer}/update', UpdateFertilizerController::class)->name('fertilizers.update');
        Route::delete('/fertilizers/{fertilizer}/delete', DeleteFertilizerController::class)->name('fertilizers.delete');

        Route::post('/man-powers/store', StoreManPowerController::class)->name('man.powers.store');
        Route::post('/man-powers/{manPower}/update', UpdateManPowerController::class)->name('man.powers.update');
        Route::delete('/man-powers/{manPower}/delete', DeleteManPowerController::class)->name('man.powers.delete');

        Route::post('/machineries/store', StoreMachineryController::class)->name('machineries.store');
        Route::post('/machineries/{machinery}/update', UpdateMachineryController::class)->name('machineries.update');
        Route::delete('/machineries/{machinery}/delete', DeleteMachineryController::class)->name('machineries.delete');

        Route::post('/type-machineries/store', StoreTypeMachineryController::class)->name('type.machineries.store');
        Route::post('/type-machineries/{typeMachinery}/update', UpdateTypeMachineryController::class)->name('type.machineries.update');
        Route::delete('/type-machineries/{typeMachinery}/delete', DeleteTypeMachineryController::class)->name('type.machineries.delete');


    });
    Route::get('/select-budge', SelectBudgetController::class)->name('select.budget');
    Route::post('/select-season/save', SaveSeasonController::class)->name('select.seasons.save');
});
