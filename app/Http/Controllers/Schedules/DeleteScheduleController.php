<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Schedule;

class DeleteScheduleController extends Controller
{
    public function __invoke(Schedule $schedule)
    {
        $contracts = Contract::where('schedule_id', $schedule->id)->count();
        if ($contracts > 0) {
            return back()->with('error', "No se puede eliminar \"{$schedule->name}\" porque tiene {$contracts} contrato(s) asociado(s).");
        }

        $schedule->delete();
        return back()->with('success', 'Horario eliminado correctamente.');
    }
}
