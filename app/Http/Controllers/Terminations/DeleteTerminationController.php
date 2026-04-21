<?php

namespace App\Http\Controllers\Terminations;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Termination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteTerminationController extends Controller
{
    public function __invoke(Termination $termination)
    {
        $user = Auth::user();

        // Solo puede anular términos de su equipo
        abort_if($termination->team_id !== $user->team_id, 403);

        DB::transaction(function () use ($termination) {
            // Reactivar contrato
            Contract::where('id', $termination->contract_id)
                ->update(['is_active' => true]);

            // Reactivar empleado
            Employee::where('id', $termination->employee_id)
                ->update(['is_active' => true]);

            // Eliminar el registro de término
            $termination->delete();
        });

        return redirect()->route('terminations.index')
            ->with('success', 'Término de faena anulado. El colaborador fue reactivado.');
    }
}
