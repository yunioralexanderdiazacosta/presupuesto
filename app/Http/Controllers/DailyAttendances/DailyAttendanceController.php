<?php

namespace App\Http\Controllers\DailyAttendances;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\LaborType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        // Empleados activos con contrato vigente
        $employees = Employee::with('activeContract')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->whereHas('activeContract')
            ->orderBy('paternal_surname')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'rut' => $e->rut,
                'position' => $e->activeContract?->position ?? '',
                'base_salary' => $e->activeContract?->base_salary ?? 0,
            ]);

        // Asistencia existente para la fecha seleccionada
        $attendances = DailyAttendance::with(['estimatedLaborType', 'estimatedCostCenter', 'registeredByUser'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        // Labores activas del equipo
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);

        // Centros de costo de la temporada
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // Resumen del día
        $totalEmployees = $employees->count();
        $totalPresent = $attendances->where('is_present', true)->count();
        $totalAbsent = $attendances->where('is_present', false)->count();
        $totalPending = $totalEmployees - $attendances->count();

        return Inertia::render('DailyAttendances/Index', [
            'employees' => $employees,
            'attendances' => $attendances,
            'laborTypes' => $laborTypes,
            'costCenters' => $costCenters,
            'selectedDate' => $date,
            'summary' => [
                'total' => $totalEmployees,
                'present' => $totalPresent,
                'absent' => $totalAbsent,
                'pending' => $totalPending,
            ],
        ]);
    }
}
