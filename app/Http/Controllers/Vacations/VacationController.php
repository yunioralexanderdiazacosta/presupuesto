<?php

namespace App\Http\Controllers\Vacations;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Vacation;
use App\Services\VacationCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VacationController extends Controller
{
    public function index(Request $request)
    {
        $user      = Auth::user();
        $teamId    = $user->team_id;
        $service   = new VacationCalculatorService();

        // Empleados con contrato indefinido activo
        $employees = Employee::with(['contracts' => fn($q) => $q->where('is_active', true)->with('vacationEntitlement')])
            ->where('team_id', $teamId)
            ->whereHas('contracts', fn($q) => $q->where('is_active', true)->where('contract_type', 'Indefinido'))
            ->orderBy('paternal_surname')
            ->get()
            ->map(function ($e) use ($service, $teamId) {
                $contract = $e->contracts->first(); // ya filtrado is_active=true
                $balance = $service->calculateBalance($e, $teamId);
                return [
                    'id'              => $e->id,
                    'name'            => $e->paternal_surname . ' ' . ($e->maternal_surname ?? '') . ', ' . $e->first_name,
                    'rut'             => $e->rut,
                    'contract_id'     => $contract?->id,
                    'anos_anteriores' => $contract?->vacationEntitlement?->anos_anteriores ?? 0,
                    ...$balance,
                ];
            });

        // Historial de vacaciones del equipo
        $vacations = Vacation::with(['employee', 'creator'])
            ->where('team_id', $teamId)
            ->latest()
            ->get()
            ->map(fn($v) => [
                'id'           => $v->id,
                'employee'     => $v->employee->paternal_surname . ' ' . ($v->employee->maternal_surname ?? '') . ', ' . $v->employee->first_name,
                'rut'          => $v->employee->rut,
                'fecha_inicio' => $v->fecha_inicio->format('d/m/Y'),
                'fecha_fin'    => $v->fecha_fin->format('d/m/Y'),
                'dias_habiles' => $v->dias_habiles,
                'notas'        => $v->notas,
                'created_by'   => $v->creator->name ?? '',
                'created_at'   => $v->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Vacations/Index', [
            'employees' => $employees,
            'vacations' => $vacations,
        ]);
    }
}
