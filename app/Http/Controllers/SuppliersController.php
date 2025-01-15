<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use Inertia\Inertia;

class SuppliersController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $suppliers = Supplier::where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Suppliers', compact('suppliers'));
    }
}
