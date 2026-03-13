<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Afp;
use Illuminate\Http\Request;

class AfpApiController extends Controller
{
    public function index()
    {
        return Afp::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->name]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:60|unique:afps,name']);

        $afp = Afp::create(['name' => $request->name]);

        return response()->json(['id' => $afp->id, 'name' => $afp->name]);
    }

    public function destroy(Afp $afp)
    {
        $afp->delete();
        return response()->json(['success' => true]);
    }
}
