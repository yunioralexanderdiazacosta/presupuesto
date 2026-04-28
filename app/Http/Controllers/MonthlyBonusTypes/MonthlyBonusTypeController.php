<?php

namespace App\Http\Controllers\MonthlyBonusTypes;

use App\Http\Controllers\Controller;
use App\Models\MonthlyBonusType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MonthlyBonusTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $bonusTypes = MonthlyBonusType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('MonthlyBonusTypes/Index', [
            'bonusTypes' => $bonusTypes,
        ]);
    }
}
