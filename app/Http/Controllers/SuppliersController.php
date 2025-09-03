<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use Inertia\Inertia;

class SuppliersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';

        $suppliers = Supplier::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })
        ->where('team_id', $user->team_id)
        ->orderBy('name', 'asc')
        ->paginate(2000)
        ->withQueryString();

        return Inertia::render('Suppliers', compact('suppliers', 'term'));
    }
}
