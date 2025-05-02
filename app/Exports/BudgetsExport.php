<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use App\Models\Budget;
use Illuminate\Support\Facades\Auth;


class BudgetsExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $budgets = Budget::with('season')->when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->where('season_id', $season_id)->get();
        return view('excels.budgets', [
            'budgets' => $budgets
        ]);
    }
}
