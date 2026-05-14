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

        // Bloquear si el contrato tiene registros relacionados
        if ($contract->terminations()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene un término de faena asociado.');
        }

        if ($contract->vacations()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene registros de vacaciones asociados.');
        }

        if ($contract->dailyYields()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene registros de tarja asociados.');
        }

        if ($contract->dailyAttendances()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene registros de asistencia diaria asociados.');
        }

        if ($contract->monthlyBonuses()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene bonos mensuales asociados.');
        }

        if ($contract->monthlyDiscounts()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene descuentos mensuales asociados.');
        }

        if ($contract->overtimeHours()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'No se puede eliminar: el contrato tiene horas extras asociadas.');
        }

        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato eliminado correctamente.');
    }
}
