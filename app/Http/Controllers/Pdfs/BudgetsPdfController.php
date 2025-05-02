<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Budget;
use Barryvdh\DomPDF\Facade\Pdf;

class BudgetsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $budgets = Budget::with('season')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->where('season_id', $season_id)->get();

        $pdf = Pdf::loadView('pdfs.budgets', ['budgets' => $budgets]);

        return $pdf->stream();
    }
}
