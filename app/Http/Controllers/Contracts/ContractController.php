<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\CompanyReason;
use App\Models\Schedule;
use App\Models\Afp;
use App\Models\HealthPlan;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $contracts = Contract::with(['employee', 'companyReason', 'schedule', 'afp', 'healthPlan', 'city'])
            ->where('team_id', $user->team_id)
            ->latest('contract_date')
            ->get();

        $employees = Employee::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->whereDoesntHave('activeContract')
            ->orderBy('paternal_surname')
            ->get()
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->paternal_surname . ' ' . ($e->maternal_surname ?? '') . ', ' . $e->first_name . ' (' . $e->rut . ')',
            ]);

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'rut'])
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name . ' (' . $c->rut . ')',
            ]);

        $schedules = Schedule::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => [
                'value' => $s->id,
                'label' => $s->name,
            ]);

        $contractTypes = ['Faena', 'Plazo Fijo', 'Indefinido'];

        $afps = Afp::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->name]);

        $healthPlans = HealthPlan::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($h) => ['value' => $h->id, 'label' => $h->name]);

        $cities = City::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);

        $maritalStatuses = [
            'Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Separado/a', 'Unión Civil',
        ];

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'employees' => $employees,
            'companyReasons' => $companyReasons,
            'schedules' => $schedules,
            'contractTypes' => $contractTypes,
            'afps' => $afps,
            'healthPlans' => $healthPlans,
            'cities' => $cities,
            'maritalStatuses' => $maritalStatuses,
        ]);
    }
}
