<?php

namespace App\Http\Controllers\Vacations;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\VacationEntitlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class UpdateVacationEntitlementController extends Controller
{
    public function __invoke(Request $request, Employee $employee)
    {
        $request->validate([
            'anos_anteriores' => 'required|integer|min:0|max:50',
        ]);

        $user = Auth::user();

        // Verificar que el empleado pertenece al equipo
        abort_if($employee->team_id !== $user->team_id, 403);

        VacationEntitlement::updateOrCreate(
            ['employee_id' => $employee->id],
            ['anos_anteriores' => $request->anos_anteriores]
        );

        return back()->with('success', 'Años anteriores actualizados.');
    }
}
