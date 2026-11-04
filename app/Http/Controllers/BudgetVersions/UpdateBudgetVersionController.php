<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormBudgetVersionRequest;
use App\Models\BudgetVersion;
use App\Support\ActiveBudgetVersion;
use App\Traits\CheckSeasonLocked;

class UpdateBudgetVersionController extends Controller
{
    use CheckSeasonLocked;

    public function __invoke(FormBudgetVersionRequest $request, BudgetVersion $budgetVersion)
    {
        $this->abortIfSeasonLocked();

        // La versión debe pertenecer a la temporada y al equipo activos
        $version = ActiveBudgetVersion::forCurrentSeason()->findOrFail($budgetVersion->id);

        $version->update([
            'name'         => $request->name,
            'observations' => $request->observations,
        ]);

        return back();
    }
}
