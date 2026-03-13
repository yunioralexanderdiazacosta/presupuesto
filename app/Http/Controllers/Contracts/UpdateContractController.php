<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Requests\Contracts\UpdateContractRequest;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;

class UpdateContractController
{
    public function __invoke(UpdateContractRequest $request, Contract $contract)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Si se activa este contrato, desactivar otros del mismo empleado
        if (($validated['is_active'] ?? false) && !$contract->is_active) {
            Contract::where('team_id', $user->team_id)
                ->where('employee_id', $contract->employee_id)
                ->where('id', '!=', $contract->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $contract->update($validated);

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato actualizado correctamente.');
    }
}
