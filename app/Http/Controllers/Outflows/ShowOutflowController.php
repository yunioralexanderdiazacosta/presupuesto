<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Models\Outflow;
use Inertia\Inertia;

class ShowOutflowController extends Controller
{
    public function __invoke(Outflow $outflow)
    {
        $outflow->load(['invoiceProduct.invoice', 'invoiceProduct.product', 'user', 'project', 'operation', 'machinery', 'team', 'season', 'costCenters.costCenter']);
        return Inertia::render('Outflows/Show', [
            'outflow' => $outflow,
        ]);
    }
}
