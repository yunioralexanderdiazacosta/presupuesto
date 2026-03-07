<?php

namespace App\Http\Controllers\Rootstocks;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormRootstockRequest;
use App\Models\Rootstock;

class UpdateRootstockController extends Controller
{
    public function __invoke(Rootstock $rootstock, FormRootstockRequest $request)
    {
        $rootstock->update([
            'name'         => $request->name,
            'observations' => $request->observations,
        ]);
    }
}
