<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;

class ParcelsExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();

        $parcels = Parcel::with(['companyReason:id,name', 'season:id,name'])->when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

         return view('excels.parcels', [
            'parcels' => $parcels
        ]);
    }
}
