<?php

namespace App\Http\Controllers\LaborRates;

use App\Http\Controllers\Controller;
use App\Models\LaborRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportLaborRatesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'action' => 'nullable|in:stream,download',
        ]);

        $user = Auth::user();
        $action = $request->get('action', 'stream');

        $laborRates = LaborRate::with(['laborType', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', session('season_id'))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Agrupar por Labor Type
        $grouped = $laborRates->groupBy(fn($lr) => $lr->laborType?->name ?? 'Sin labor asociada');
        // Ordenar grupos alfabéticamente, "Sin labor asociada" al final
        $grouped = $grouped->sortKeys()->put(
            'Sin labor asociada',
            $grouped->pull('Sin labor asociada') ?? collect()
        )->filter(fn($items) => $items->isNotEmpty());

        $teamName = $user->currentTeam?->name ?? 'Equipo';

        $pdf = Pdf::loadView('pdfs.labor-rates-catalog', [
            'grouped' => $grouped,
            'teamName' => $teamName,
            'totalRates' => $laborRates->count(),
            'date' => now()->format('d/m/Y'),
        ]);

        $pdf->setPaper('letter', 'portrait');
        $filename = 'catalogo-tratos.pdf';

        if ($action === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
