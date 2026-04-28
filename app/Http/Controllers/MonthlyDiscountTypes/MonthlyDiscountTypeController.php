<?php

namespace App\Http\Controllers\MonthlyDiscountTypes;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDiscountType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MonthlyDiscountTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $discountTypes = MonthlyDiscountType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('MonthlyDiscountTypes/Index', [
            'discountTypes' => $discountTypes,
        ]);
    }
}
