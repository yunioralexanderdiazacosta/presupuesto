<?php

namespace App\Http\Controllers\Rootstocks;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormRootstockRequest;
use App\Models\Rootstock;
use Illuminate\Support\Facades\Auth;

class StoreRootstockController extends Controller
{
    public function __invoke(FormRootstockRequest $request)
    {
        $user = Auth::user();

        Rootstock::create([
            'name'         => $request->name,
            'observations' => $request->observations,
            'team_id'      => $user->team_id,
        ]);
    }
}
