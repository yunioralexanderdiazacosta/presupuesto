<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\Project;
use App\Models\Operation;
use App\Models\Machinery;
use App\Models\Team;
use App\Models\Season;
use App\Models\CostCenter;
use Inertia\Inertia;

class EditOutflowController extends Controller
{
    public function __invoke(Outflow $outflow)
    {
        $outflow->load(['costCenters']);
        return Inertia::render('Outflows/Edit', [
            'outflow' => $outflow,
            'invoiceProducts' => InvoiceProduct::with('invoice', 'product')->get(),
            'projects' => Project::all(),
            'operations' => Operation::all(),
            'machineries' => Machinery::all(),
            'teams' => Team::all(),
            'seasons' => Season::all(),
            'costCenters' => CostCenter::all(),
        ]);
    }
}
