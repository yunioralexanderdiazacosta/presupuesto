<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class GetAvailableEmployeesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Employee::where('team_id', $user->team_id)
            ->whereDoesntHave('contracts', fn($q) => $q->where('is_active', true))
            ->orderBy('paternal_surname')
            ->get()
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->paternal_surname . ' ' . ($e->maternal_surname ?? '') . ', ' . $e->first_name . ' (' . $e->rut . ')',
            ]);
    }
}
