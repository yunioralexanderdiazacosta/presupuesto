<?php

namespace App\Http\Controllers;

use App\Models\Consumption;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConsumptionController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $season_id = session('season_id');

        $products = \App\Models\Product::where('team_id', $user->team_id)
            ->select('id as value', 'name as label')
            ->orderBy('name')->get();

        $costCenters = \App\Models\CostCenter::where('season_id', $season_id)
            ->whereHas('season.team', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->select('id as value', 'name as label')
            ->orderBy('name')->get();

        $operations = \App\Models\Operation::select('id as value', 'name as label')->orderBy('name')->get();
        $machineries = \App\Models\Machinery::select('id as value', 'cod_machinery as label')->orderBy('cod_machinery')->get();
        $projects = \App\Models\Project::select('id as value', 'name as label')->orderBy('name')->get();

        $invoiceLines = \App\Models\InvoiceProduct::with('invoice')
            ->whereIn('product_id', $products->pluck('value'))
            ->get()
            ->groupBy('product_id')
            ->map(function ($lines) {
                return $lines->map(function ($line) {
                    return [
                        'value' => $line->id,
                        'label' => 'Factura #' . ($line->invoice->number ?? '-') .
                            ' - Línea ' . $line->id .
                            ' (' . $line->amount . ' unidades)' .
                            ' - ' . ($line->invoice->date ?? ''),
                    ];
                })->values();
            });

        return Inertia::render('Consumptions/Create', [
            'products' => $products,
            'costCenters' => $costCenters,
            'operations' => $operations,
            'machineries' => $machineries,
            'projects' => $projects,
            'invoiceLinesByProduct' => $invoiceLines,
            'userId' => $user->id,
            'teamId' => $user->team_id,
            'seasonId' => $season_id,
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        $consumptions = \App\Models\Consumption::with(['costCenter', 'user', 'items.product'])
            ->when($term, function($query, $search) {
                $query->where('id', 'like', "%$search%")
                    ->orWhereHas('costCenter', function($q) use ($search) {
                        $q->where('name', 'like', "%$search%") ;
                    });
            })
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Consumptions', [
            'consumptions' => $consumptions,
            'term' => $term,
            'success' => session('success')
        ]);
    }
}
