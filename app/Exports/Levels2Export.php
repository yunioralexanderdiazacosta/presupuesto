<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Level2;

class Levels2Export implements FromView, ShouldAutoSize
{
    public $term;
    public $id;

    public function __construct($term, $id)
    {
        $this->term = $term;
        $this->id = $id;
    }

    public function view(): View
    {
        $term = $request->term ?? '';  

        $levels = Level2::with('level1')->when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level1_id', $this->id)->get();

        return view('excels.levels2', [
            'levels' => $levels
        ]);

    }
}
