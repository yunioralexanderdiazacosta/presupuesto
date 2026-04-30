<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branches\FormBranchRequest;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class StoreBranchController extends Controller
{
    public function __invoke(FormBranchRequest $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        if ($request->boolean('is_default')) {
            Branch::where('team_id', $user->team_id)
                ->where('season_id', $seasonId)
                ->update(['is_default' => false]);
        }

        Branch::create([
            'name'       => $request->name,
            'is_default' => $request->boolean('is_default'),
            'team_id'    => $user->team_id,
            'season_id'  => $seasonId,
        ]);
    }
}
