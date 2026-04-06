<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $employees = Employee::where('team_id', $user->team_id)
            ->orderBy('paternal_surname')
            ->orderBy('maternal_surname')
            ->orderBy('first_name')
            ->get();

        $nationalities = [
            'Chilena', 'Argentina', 'Boliviana', 'Peruana', 'Colombiana',
            'Venezolana', 'Ecuatoriana', 'Brasileña', 'Paraguaya', 'Uruguaya',
            'Mexicana', 'Haitiana', 'Dominicana', 'Cubana', 'Española', 'Otra',
        ];

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'nationalities' => $nationalities,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new EmployeesImport, $request->file('file'));
            return response()->json(['message' => 'Importación exitosa']);
        } catch (ValidationException $e) {
            $failures = array_map(fn($f) => [
                'row' => $f->row(),
                'attribute' => $f->attribute(),
                'errors' => $f->errors(),
            ], $e->failures());
            return response()->json(['message' => 'Errores en el archivo', 'failures' => $failures], 422);
        }
    }

    public function template()
    {
        return Excel::download(new EmployeesTemplateExport, 'plantilla_colaboradores.xlsx');
    }
}
