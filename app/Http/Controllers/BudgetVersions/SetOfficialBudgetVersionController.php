<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Support\ActiveBudgetVersion;
use App\Traits\CheckSeasonLocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetOfficialBudgetVersionController extends Controller
{
    use CheckSeasonLocked;

    public function __invoke(Request $request, BudgetVersion $budgetVersion)
    {
        abort_unless(ActiveBudgetVersion::isAdmin($request->user()), 403, 'Solo los administradores pueden cambiar la versión oficial.');
        $this->abortIfSeasonLocked();

        $version = ActiveBudgetVersion::forCurrentSeason()->findOrFail($budgetVersion->id);

        if ($version->is_official) {
            return back();
        }

        DB::transaction(function () use ($version) {
            BudgetVersion::where('season_id', $version->season_id)->update(['is_official' => false]);
            $version->update(['is_official' => true]);
        });

        return back();
    }
}
