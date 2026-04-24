<?php

namespace App\Http\Controllers\Holidays;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Support\Facades\Auth;

class DeleteHolidayController extends Controller
{
    public function __invoke(Holiday $holiday)
    {
        $user = Auth::user();

        // Solo puede eliminar feriados de su equipo (no los nacionales)
        abort_if(is_null($holiday->team_id), 403, 'No se pueden eliminar feriados nacionales.');
        abort_if($holiday->team_id !== $user->team_id, 403);

        $holiday->delete();

        return redirect()->route('holidays.index')
            ->with('success', 'Feriado eliminado.');
    }
}
