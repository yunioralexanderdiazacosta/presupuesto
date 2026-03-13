<?php

namespace App\Http\Controllers\Employees;

use App\Models\Employee;

class DeleteEmployeeController
{
    public function __invoke(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Colaborador eliminado correctamente.');
    }
}
