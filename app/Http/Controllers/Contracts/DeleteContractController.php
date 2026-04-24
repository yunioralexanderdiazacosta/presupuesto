<?php

namespace App\Http\Controllers\Contracts;

use App\Models\Contract;
use Illuminate\Support\Facades\Auth;

class DeleteContractController
{
    public function __invoke(Contract $contract)
    {
        $user = Auth::user();
        abort_if($contract->team_id !== $user->team_id, 403);

        // Bloquear si el contrato tiene registros posteriores relacionados
        if ($contract->terminations()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene un término de faena asociado.');
        }

        if ($contract->vacations()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene registros de vacaciones asociados.');
        }

        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato eliminado correctamente.');
    }
}
