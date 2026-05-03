<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Variety;

class DeleteVarietyController extends Controller
{
    public function __invoke(Variety $variety)
    {
        if ($variety->hasAssociatedRecords()) {
            return back()->withErrors([
                'variety' => 'No se puede eliminar la variedad porque tiene registros asociados (centros de costo, evaluaciones de proyecto u otros). Elimine primero los registros relacionados.'
            ]);
        }

        $variety->delete();
    }
}
