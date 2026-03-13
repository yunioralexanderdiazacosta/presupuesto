<?php

namespace App\Http\Controllers\Employees;

use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class StoreEmployeeController
{
    public function __invoke(StoreEmployeeRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Colaborador registrado correctamente.');
    }
}
