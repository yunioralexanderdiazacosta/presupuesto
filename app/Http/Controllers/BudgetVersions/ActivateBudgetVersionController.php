<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Support\ActiveBudgetVersion;
use Illuminate\Http\Request;

class ActivateBudgetVersionController extends Controller
{
    public function __invoke(Request $request, BudgetVersion $budgetVersion)
    {
        abort_unless(ActiveBudgetVersion::isAdmin($request->user()), 403, 'Solo los administradores pueden cambiar la versión de presupuesto.');

        $version = ActiveBudgetVersion::forCurrentSeason()->findOrFail($budgetVersion->id);

        ActiveBudgetVersion::set($version);

        return back();
    }
}
