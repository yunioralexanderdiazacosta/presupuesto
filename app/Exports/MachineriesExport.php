<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Machinery;

class MachineriesExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();

        $machineries = Machinery::when($this->term, function ($query, $search) {
            $query->where('cod_machinery', 'like', '%'.$search.'%');
        })->with('typeMachinery')->where('team_id', $user->team_id)->get();

        return view('excels.machineries', [
            'machineries' => $machineries
        ]);
    }
}
