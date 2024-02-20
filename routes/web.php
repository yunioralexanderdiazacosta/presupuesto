<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgrochemicalsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\Teams\StoreTeamController;
use App\Http\Controllers\Teams\UpdateTeamController;
use App\Http\Controllers\Teams\DeleteTeamController;
use App\Http\Controllers\Teams\ActivateInactivateTeamController;
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
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/teams', TeamsController::class)->name('teams.index');
    Route::get('/agrochemicals', AgrochemicalsController::class)->name('agrochemicals.index');

    Route::post('/teams/store', StoreTeamController::class)->name('teams.store');
    Route::post('teams/{user}/update', UpdateTeamController::class)->name('teams.update');
    Route::delete('/teams/{user}/delete', DeleteTeamController::class)->name('teams.delete');
    Route::post('/teams/{user}//activate/inactivate', ActivateInactivateTeamController::class)->name('teams.activate.inactivate');
});
