<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Requests\Contracts\StoreContractRequest;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreContractController
{
    public function __invoke(StoreContractRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;

        // Si el nuevo contrato es activo, desactivar otros del mismo empleado
        if ($validated['is_active'] ?? true) {
            Contract::where('team_id', $user->team_id)
                ->where('employee_id', $validated['employee_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        Contract::create($validated);

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato registrado correctamente.');
    }
}
