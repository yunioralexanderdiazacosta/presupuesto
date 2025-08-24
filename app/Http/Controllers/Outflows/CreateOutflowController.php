<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\InvoiceProduct;
use App\Models\Project;
use App\Models\Operation;
use App\Models\Machinery;
use App\Models\Team;
use App\Models\Season;
use App\Models\CostCenter;
use Inertia\Inertia;

class CreateOutflowController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $invoiceProductId = $request->input('invoice_product_id');
        $invoiceProduct = null;
        if ($invoiceProductId) {
            // Cargar producto y su unidad para mostrar de manera estática
            $invoiceProduct = InvoiceProduct::with(['invoice', 'product.unit'])->find($invoiceProductId);
        }

        return Inertia::render('Outflows/Create', [
            'invoiceProduct' => $invoiceProduct,
            'projects' => Project::where('team_id', $user->team_id)
                ->get()
                ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]),
            'operations' => Operation::all()
                ->map(fn($o) => ['value' => $o->id, 'label' => $o->name]),
            'machineries' => Machinery::all()->map(fn($m) => ['value' => $m->id, 'label' => $m->name ?? ($m->cod_machinery ?? 'Maquinaria #' . $m->id)]),
            'teams' => Team::where('id', $user->team_id)->get(),
            'seasons' => Season::where('id', session('season_id'))->get(),
            'costCenters' => CostCenter::whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })->get(),
        ]);
    }
}
