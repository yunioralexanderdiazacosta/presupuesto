<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use Illuminate\Support\Facades\Auth;

class GetParcelsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Parcel::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($item) => ['value' => $item->id, 'label' => $item->name]);
    }
}
