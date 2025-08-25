<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use App\Models\Invoice;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OutflowsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        // Traer facturas del equipo y temporada actual, con sus productos y proveedor
    $invoices = Invoice::with(['supplier', 'invoiceProducts.product.unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get();

        $rows = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                // Filtro por término de búsqueda
                if ($term && stripos($invoice->number_document, $term) === false) {
                    continue;
                }
                $rows[] = [
                    'invoice_id'        => $invoice->id,
                    'number_document'   => $invoice->number_document,
                    'supplier'          => $invoice->supplier->name ?? '-',
                    'product'           => $invoiceProduct->product->name ?? '-',
                    'unit'              => $invoiceProduct->product->unit->name ?? '-',
                    'quantity'          => $invoiceProduct->quantity ?? $invoiceProduct->amount ?? '-',
                    'invoice_product_id'=> $invoiceProduct->id,
                ];
            }
        }

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Cargar catálogos para selects
        $projects = \App\Models\Project::where('team_id', $user->team_id)
            ->get()
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);
        $operations = \App\Models\Operation::all()
            ->map(fn($o) => ['value' => $o->id, 'label' => $o->name]);
        $machineries = \App\Models\Machinery::where('team_id', $user->team_id)
            ->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => trim($m->cod_machinery . ' - ' . $m->brand)
            ]);
        $cost_centers = \App\Models\CostCenter::whereHas('season', function($q) use ($user) {
            $q->where('team_id', $user->team_id);
        })->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);

        // Traer detalles de salidas ya registradas con sus centros de costo
        $outflowDetails = Outflow::with(['costCenters.costCenter', 'project', 'operation', 'machinery', 'user'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderByDesc('date')
            ->take(100)
            ->get()
            ->map(function($outflow) {
                return [
                    'id' => $outflow->id,
                    'date' => $outflow->date,
                    'project' => $outflow->project->name ?? '',
                    'operation' => $outflow->operation->name ?? '',
                    'machinery' => $outflow->machinery ? trim($outflow->machinery->cod_machinery . ' - ' . $outflow->machinery->brand) : '',
                    'user' => $outflow->user->name ?? '',
                    'quantity' => $outflow->quantity,
                    'notes' => $outflow->notes,
                    'cost_centers' => $outflow->costCenters->map(function($cc) {
                        return [
                            'name' => $cc->costCenter->name ?? '',
                            'observations' => $cc->observations
                        ];
                    })->toArray(),
                ];
            });

  // Agrupaciones para el select de agrupación
    $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($season_id, $user) {
        $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $season_id);
    }])
    ->where('season_id', $season_id)
    ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
    ->get()
    ->map(fn($g) => [
        'id' => $g->id,
        'name' => $g->name,
        'cost_centers' => $g->costCenters->map(fn($cc) => [
            'id' => $cc->id,
            'name' => $cc->name
        ])->values(),
    ]);

        return Inertia::render('Outflows', [
            'outflows' => $paginated,
            'term'     => $term,
            'projects' => $projects,
            'operations' => $operations,
            'machineries' => $machineries,
            'cost_centers' => $cost_centers,
            'outflowDetails' => $outflowDetails,
            'groupings' => $groupings,
        ]);
    }
}
