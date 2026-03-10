<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Support\Facades\Auth;

class GetCarriersController extends Controller
{
    public function __invoke()
    {
        return Carrier::where('team_id', Auth::user()->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn($item) => ['value' => $item->id, 'label' => $item->name, 'is_active' => $item->is_active]);
    }
}
