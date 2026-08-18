<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Machinery;
use App\Models\TypeMachinery;
use App\Models\CompanyReason;
use App\Models\Branch;
use App\Models\Counter;
use Inertia\Inertia;

class MachineriesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($companyReason){
            return [
                'label' => $companyReason->name,
                'value' => $companyReason->id
            ];
        });

         $typeMachineries = TypeMachinery::where('team_id', $user->team_id)->get()->transform(function($type){
            return [
                'label' => $type->name,
                'value' => $type->id
            ];
        });

        $counters = Counter::all()->transform(function($counter){
            return [
                'label' => $counter->name,
                'value' => $counter->id
            ];
        });

        $machineries = Machinery::with(['typeMachinery', 'counter', 'companyReason', 'branch'])
            ->where('team_id', $user->team_id)
            ->orderBy('cod_machinery')
            ->get();

        $branches = Branch::where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['label' => $b->name, 'value' => $b->id]);

        return Inertia::render('Machineries', compact('machineries', 'companyReasons', 'typeMachineries', 'counters', 'branches', 'term'));
    }
}
