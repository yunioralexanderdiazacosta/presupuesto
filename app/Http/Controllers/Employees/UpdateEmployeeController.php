<?php

namespace App\Http\Controllers\Employees;

use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Employee;

class UpdateEmployeeController
{
    public function __invoke(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Colaborador actualizado correctamente.');
    }
}
