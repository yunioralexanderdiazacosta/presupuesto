<?php

namespace App\Http\Controllers\BudgetVersions;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Services\BudgetCopyService;
use App\Support\ActiveBudgetVersion;
use App\Traits\CheckSeasonLocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteBudgetVersionController extends Controller
{
    use CheckSeasonLocked;

    public function __invoke(Request $request, BudgetVersion $budgetVersion)
    {
        abort_unless(ActiveBudgetVersion::isAdmin($request->user()), 403, 'Solo los administradores pueden eliminar versiones de presupuesto.');
        $this->abortIfSeasonLocked();

        $version = ActiveBudgetVersion::forCurrentSeason()->findOrFail($budgetVersion->id);

        if ($version->is_official) {
            return back()->with('error', 'No se puede eliminar la versión oficial. Marque otra versión como oficial primero.');
        }

        DB::transaction(function () use ($version) {
            // Se eliminan explícitamente los items de cada línea antes que la línea misma
            foreach (BudgetCopyService::TYPES as $config) {
                $table = (new $config['model'])->getTable();
                $lineIds = DB::table($table)->where('budget_version_id', $version->id)->pluck('id');

                foreach ($lineIds->chunk(500) as $chunk) {
                    DB::table($config['pivot'])->whereIn($config['fk'], $chunk)->delete();
                }

                DB::table($table)->where('budget_version_id', $version->id)->delete();
            }

            $version->delete();
        });

        if (session(ActiveBudgetVersion::SESSION_KEY) == $version->id) {
            ActiveBudgetVersion::forget();
        }

        return back()->with('success', 'Versión eliminada correctamente.');
    }
}
