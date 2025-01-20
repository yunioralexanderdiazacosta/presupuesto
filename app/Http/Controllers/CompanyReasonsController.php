<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyReason;
use Inertia\Inertia;

class CompanyReasonsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('CompanyReasons', compact('companyReasons'));
    }
}
