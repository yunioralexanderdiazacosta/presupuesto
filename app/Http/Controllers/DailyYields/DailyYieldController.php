<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Models\BonusType;
use App\Models\CostCenter;
use App\Models\DailyAttendance;
use App\Models\DailyYield;
use App\Models\LaborType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyYieldController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        // Empleados presentes en la fecha (solo los que tienen asistencia marcada como presente)
        $presentIds = DailyAttendance::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->where('is_present', true)
            ->pluck('employee_id');

        // Tarjas existentes para la fecha
        $yields = DailyYield::with(['employee', 'laborType', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->orderBy('employee_id')
            ->get();

        // Empleados presentes (para select en formulario)
        $presentEmployees = \App\Models\Employee::whereIn('id', $presentIds)
            ->orderBy('paternal_surname')
            ->get()
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->full_name,
                'rut' => $e->rut,
            ]);

        // Labores activas
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($l) => [
                'value' => $l->id,
                'label' => $l->name,
                'default_rate' => $l->default_rate,
                'default_bonus' => $l->default_bonus,
                'unit_id' => $l->unit_id,
            ]);

        // Bonos activos
        $bonusTypes = BonusType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name,
                'default_amount' => $b->default_amount,
            ]);

        // Centros de costo
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // Resumen del día
        $totalAmount = $yields->sum('amount');
        $totalBonus = $yields->sum('bonus_amount');
        $totalHours = $yields->sum('hours');
        $employeesWithYields = $yields->pluck('employee_id')->unique()->count();

        // Verificar si hay asistencia para la fecha
        $hasAttendance = DailyAttendance::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->exists();

        return Inertia::render('DailyYields/Index', [
            'yields' => $yields,
            'presentEmployees' => $presentEmployees,
            'laborTypes' => $laborTypes,
            'bonusTypes' => $bonusTypes,
            'costCenters' => $costCenters,
            'selectedDate' => $date,
            'hasAttendance' => $hasAttendance,
            'summary' => [
                'totalPresent' => $presentIds->count(),
                'employeesWithYields' => $employeesWithYields,
                'totalAmount' => $totalAmount,
                'totalBonus' => $totalBonus,
                'totalHours' => $totalHours,
            ],
        ]);
    }
}
