<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateDollarPriceController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'dollar_price' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $user->update(['dollar_price' => $request->dollar_price]);

        return response()->json(['dollar_price' => $user->dollar_price]);
    }
}
