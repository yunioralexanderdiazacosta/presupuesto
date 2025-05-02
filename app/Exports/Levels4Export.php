<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Level4;


class Levels4Export implements FromView, ShouldAutoSize
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
        $levels = Level4::with('level3', 'level3.level2', 'level3.level2.level1')->when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level3_id', $this->id)->get();

        return view('excels.levels4', [
            'levels' => $levels
        ]);
    }
}
