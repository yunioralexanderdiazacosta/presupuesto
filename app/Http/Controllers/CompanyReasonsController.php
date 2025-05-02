<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyReason;
use Inertia\Inertia;

class CompanyReasonsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $companyReasons = CompanyReason::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        return Inertia::render('CompanyReasons', compact('companyReasons', 'term'));
    }
}
