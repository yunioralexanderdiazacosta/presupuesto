<?php

namespace App\Http\Controllers;

use App\Models\CompanyReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\CostCenter;
use App\Models\Fruit;
use App\Models\Parcel;
use App\Models\DevelopmentState;
use Inertia\Inertia;
use App\Exports\CostCentersTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CostCentersImport;

class CostCentersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $season_id = session('season_id');

        $season = Season::select('name')->where('id', $season_id)->first();

        $fruits = Fruit::where('team_id', $user->team_id)->get()->transform(function($fruit){
            return [
                'label' => $fruit->name,
                'value' => $fruit->id
            ];
        });


        $parcels = Parcel::where('team_id', $user->team_id)->get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });





        $costCenters = CostCenter::with('fruit:id,name', 'variety:id,name','developmentState:id,name','companyReason:id,name','groupings:id,name')
            ->where('season_id', $season_id)
            ->when($request->term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->whereHas('season.team', function($query) use ($user){
                $query->where('team_id', $user->team_id);
            })
            ->paginate(10);

        $developmentStates = DevelopmentState::get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        return Inertia::render('CostCenters', compact('costCenters', 'season', 'parcels', 'developmentStates', 'fruits', 'term','companyReasons'));
    }   

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        try {
            Excel::import(new CostCentersImport, $request->file('file'));
            return response()->json(['message' => 'Importación exitosa']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = array_map(fn($failure) => [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ], $e->failures());
            return response()->json([
                'message' => 'Errores en el archivo',
                'failures' => $failures,
            ], 422);
        }
    }

    /**
     * Descargar plantilla de importación de centros de costo
     */
    public function template()
    {
        return Excel::download(new CostCentersTemplateExport, 'plantilla_centros_costo.xlsx');
    }
}
