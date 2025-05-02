<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Fruit;


class FruitsExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();

        $fruits = Fruit::when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

        return view('excels.fruits', [
            'fruits' => $fruits
        ]);
    }
}
