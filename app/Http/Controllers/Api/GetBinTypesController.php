<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BinType;
use Illuminate\Support\Facades\Auth;

class GetBinTypesController extends Controller
{
    public function __invoke()
    {
        return BinType::where('team_id', Auth::user()->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn($item) => ['value' => $item->id, 'label' => $item->name, 'is_active' => $item->is_active]);
    }
}
