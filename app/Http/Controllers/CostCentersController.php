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
use App\Models\Rootstock;
use App\Models\Variety;
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

        $fruits = Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get()
            ->transform(function($fruit){
                return [
                    'label' => $fruit->name,
                    'value' => $fruit->id
                ];
            });

        $parcels = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderBy('name')
            ->get()
            ->transform(function($parcel){
                return [
                    'label' => $parcel->name,
                    'value' => $parcel->id
                ];
            });

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get()
            ->transform(function($company){
                return [
                    'label' => $company->name,
                    'value' => $company->id
                ];
            });





        $costCenters = CostCenter::with('fruit:id,name', 'variety:id,name', 'parcel:id,name', 'developmentState:id,name','companyReason:id,name','groupings:id,name')
            ->where('season_id', $season_id)
            ->when($request->term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->whereHas('season.team', function($query) use ($user){
                $query->where('team_id', $user->team_id);
            })
            ->paginate(100);

        $developmentStates = DevelopmentState::get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        $varieties = Variety::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'fruit_id'])
            ->map(fn($v) => ['label' => $v->name, 'value' => $v->id, 'fruit_id' => $v->fruit_id]);

        $rootstocks = Rootstock::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($r) => ['label' => $r->name, 'value' => $r->id]);

        // Lista de cuarteles formateada para el select del modal de variedades
        $costCentersSelect = CostCenter::where('season_id', $season_id)
            ->whereHas('season', fn($q) => $q->where('team_id', $user->team_id))
            ->orderBy('name')
            ->get(['id', 'name', 'surface'])
            ->map(fn($c) => ['label' => $c->name, 'value' => $c->id, 'surface' => (float) $c->surface]);

        // Lista de otras temporadas del equipo para el modal de copia
        $seasons = Season::where('team_id', $user->team_id)
            ->where('id', '!=', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['label' => $s->name, 'value' => $s->id]);

        return Inertia::render('CostCenters', compact('costCenters', 'season', 'parcels', 'developmentStates', 'fruits', 'term', 'companyReasons', 'varieties', 'rootstocks', 'costCentersSelect', 'seasons'));
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
