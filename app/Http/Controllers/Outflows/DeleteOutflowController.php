<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Models\Outflow;

class DeleteOutflowController extends Controller
{
    public function __invoke(Outflow $outflow)
    {
        $outflow->delete();
        return redirect()->route('outflows.index')->with('success', 'Salida eliminada correctamente.');
    }
}
