<?php

namespace App\Http\Controllers\Terminations;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Termination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreTerminationController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'contract_ids'       => 'required|array|min:1',
            'contract_ids.*'     => 'required|integer|exists:contracts,id',
            'causal_termino_id'  => 'required|integer|exists:causales_termino,id',
            'fecha_termino'      => 'required|date',
            'notas'              => 'nullable|string|max:500',
            'settlement'         => 'nullable|integer|min:0',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            foreach ($request->contract_ids as $contractId) {
                $contract = Contract::with('employee')
                    ->where('id', $contractId)
                    ->where('team_id', $user->team_id)
                    ->where('is_active', true)
                    ->where('contract_type', 'Faena')
                    ->firstOrFail();

                // Registrar el término
                Termination::create([
                    'team_id'           => $user->team_id,
                    'contract_id'       => $contract->id,
                    'employee_id'       => $contract->employee_id,
                    'causal_termino_id' => $request->causal_termino_id,
                    'fecha_termino'     => $request->fecha_termino,
                    'notas'             => $request->notas,
                    'settlement'        => $request->settlement,
                    'created_by'        => $user->id,
                ]);

                // Inactivar contrato (el empleado sigue activo para futuros contratos)
                $contract->update(['is_active' => false]);
            }
        });

        return redirect()->route('terminations.index')
            ->with('success', 'Término(s) de faena registrado(s) correctamente.');
    }
}
