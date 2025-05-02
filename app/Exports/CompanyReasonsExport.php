<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyReason;

class CompanyReasonsExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user(); 

        $companyReasons = CompanyReason::when($this->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        return view('excels.company-reasons', [
            'companyReasons' => $companyReasons
        ]);
    }
}
