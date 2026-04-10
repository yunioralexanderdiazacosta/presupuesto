<?php

namespace App\Http\Controllers\LaborTypes;

use App\Http\Controllers\Controller;
use App\Models\LaborType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportLaborTypesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'action' => 'nullable|in:stream,download',
        ]);

        $user = Auth::user();
        $action = $request->get('action', 'stream');

        $laborTypes = LaborType::with(['level3', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Agrupar por Level3
        $grouped = $laborTypes->groupBy(fn($lt) => $lt->level3?->name ?? 'Sin clasificación');
        // Ordenar grupos alfabéticamente, "Sin clasificación" al final
        $grouped = $grouped->sortKeys()->put(
            'Sin clasificación',
            $grouped->pull('Sin clasificación') ?? collect()
        )->filter(fn($items) => $items->isNotEmpty());

        $teamName = $user->currentTeam?->name ?? 'Equipo';

        $pdf = Pdf::loadView('pdfs.labor-types-catalog', [
            'grouped' => $grouped,
            'teamName' => $teamName,
            'totalLabors' => $laborTypes->count(),
            'date' => now()->format('d/m/Y'),
        ]);

        $pdf->setPaper('letter', 'portrait');
        $filename = 'catalogo-labores.pdf';

        if ($action === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
