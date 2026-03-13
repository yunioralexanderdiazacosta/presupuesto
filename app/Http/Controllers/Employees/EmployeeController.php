<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
