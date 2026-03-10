<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackingHouse;
use Illuminate\Support\Facades\Auth;

class GetPackingHousesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return PackingHouse::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->name,
            ]);
    }
}
