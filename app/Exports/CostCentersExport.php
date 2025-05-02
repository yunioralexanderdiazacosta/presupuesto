<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\CostCenter;

class CostCentersExport implements FromView, ShouldAutoSize
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
        
        $costCenters = CostCenter::where('season_id', $season_id)->when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get();

        return view('excels.cost-centers', [
            'costCenters' => $costCenters
        ]);
    }
}
