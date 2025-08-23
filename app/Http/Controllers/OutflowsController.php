<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OutflowsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        $outflows = Outflow::whereHas('invoiceProduct.invoice', function ($query) use ($user, $season_id) {
                $query->where('team_id', $user->team_id)
                      ->where('season_id', $season_id);
            })
            ->with(['invoiceProduct.invoice', 'invoiceProduct.product', 'user', 'project', 'operation', 'machinery', 'team', 'season', 'costCenters.costCenter'])
            ->when($term, function ($query, $term) {
                $query->whereHas('invoiceProduct.invoice', function ($q) use ($term) {
                    $q->where('number_document', 'like', "%{$term}%");
                });
            })
            ->paginate(10)
            ->withQueryString()
            ->through(function ($outflow) {
                return [
                    'id'           => $outflow->id,
                    'date'         => $outflow->date,
                    'invoice'      => $outflow->invoiceProduct->invoice,
                    'product'      => $outflow->invoiceProduct->product,
                    'quantity'     => $outflow->quantity,
                ];
            });

        return Inertia::render('Outflows', compact('outflows', 'term'));
    }
}
