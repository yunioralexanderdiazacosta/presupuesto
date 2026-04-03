<?php

namespace App\Http\Controllers\BonusTypes;

use App\Http\Controllers\Controller;
use App\Models\BonusType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BonusTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $bonusTypes = BonusType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('BonusTypes/Index', [
            'bonusTypes' => $bonusTypes,
        ]);
    }
}
