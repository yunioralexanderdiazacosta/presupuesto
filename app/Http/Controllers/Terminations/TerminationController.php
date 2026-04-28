<?php

namespace App\Http\Controllers\Terminations;

use App\Http\Controllers\Controller;
use App\Models\CausalTermino;
use App\Models\Contract;
use App\Models\Termination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TerminationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Contratos activos del equipo (todos los tipos de contrato)
        $activeContracts = Contract::with('employee')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'value'    => $c->id,
                'label'    => $c->employee->paternal_surname . ' ' . ($c->employee->maternal_surname ?? '') . ', ' . $c->employee->first_name . ' (' . $c->employee->rut . ') - ' . $c->contract_type,
                'employee_id' => $c->employee_id,
                'contract_date' => $c->contract_date,
            ]);

        $causales = CausalTermino::where('activa', true)
            ->where('aplica_faena', true)
            ->orderBy('orden')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->articulo . ' - ' . $c->nombre,
            ]);

        // Historial de términos del equipo
        $terminations = Termination::with(['employee', 'contract', 'causalTermino', 'creator'])
            ->where('team_id', $user->team_id)
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id'           => $t->id,
                'employee'     => $t->employee->paternal_surname . ' ' . ($t->employee->maternal_surname ?? '') . ', ' . $t->employee->first_name,
                'rut'          => $t->employee->rut,
                'fecha_termino' => $t->fecha_termino->format('d/m/Y'),
                'causal'       => $t->causalTermino->articulo . ' - ' . $t->causalTermino->nombre,
                'notas'        => $t->notas,
                'settlement'   => $t->settlement,
                'created_by'   => $t->creator->name ?? '',
                'created_at'   => $t->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Terminations/Index', [
            'activeContracts' => $activeContracts,
            'causales'        => $causales,
            'terminations'    => $terminations,
        ]);
    }
}
