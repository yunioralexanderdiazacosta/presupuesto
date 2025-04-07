<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Machinery;
use App\Models\TypeMachinery;
use App\Models\CompanyReason;
use Inertia\Inertia;

class MachineriesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

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

        $machineries = Machinery::with('typeMachinery')->where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Machineries', compact('machineries', 'companyReasons', 'typeMachineries'));
    }
}
