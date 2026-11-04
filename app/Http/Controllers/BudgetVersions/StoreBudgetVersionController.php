<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormBudgetVersionRequest;
use App\Models\BudgetVersion;
use App\Services\BudgetCopyService;
use App\Support\ActiveBudgetVersion;
use App\Traits\CheckSeasonLocked;
use Illuminate\Support\Facades\DB;

class StoreBudgetVersionController extends Controller
{
    use CheckSeasonLocked;

    public function __invoke(FormBudgetVersionRequest $request, BudgetCopyService $copyService)
    {
        $this->abortIfSeasonLocked();

        $source = ActiveBudgetVersion::forCurrentSeason()->findOrFail($request->source_version_id);
        $season = $source->season;

        $version = DB::transaction(function () use ($request, $copyService, $source, $season) {
            $version = BudgetVersion::create([
                'season_id'    => $season->id,
                'team_id'      => $season->team_id,
                'name'         => $request->name,
                'observations' => $request->observations,
                'is_official'  => false,
                'created_by'   => $request->user()->id,
            ]);

            // Una versión es un presupuesto completo: se copian todos los rubros
            $copyService->copy($source, $season, $version, array_keys(BudgetCopyService::TYPES));

            return $version;
        });

        if ($request->boolean('activate')) {
            ActiveBudgetVersion::set($version);
        }

        return back();
    }
}
