<?php

namespace App\Http\Controllers\Exporters;

use App\Models\Exporter;

class DeleteExporterController
{
    public function __invoke(Exporter $exporter)
    {
        $exporter->delete();

        return back()->with('success', 'Exportadora eliminada correctamente.');
    }
}
