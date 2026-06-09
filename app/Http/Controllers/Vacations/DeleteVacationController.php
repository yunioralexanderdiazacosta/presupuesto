<?php

namespace App\Http\Controllers\Vacations;

use App\Http\Controllers\Controller;
use App\Models\Vacation;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteVacationController extends Controller
{
    public function __invoke(Vacation $vacation)
    {
        $user = Auth::user();
        abort_if($vacation->team_id !== $user->team_id, 403);

        $vacation->delete();

        return redirect()->route('vacations.index')
            ->with('success', 'Registro de vacaciones eliminado.');
    }
}
