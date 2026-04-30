<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branches\FormBranchRequest;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class UpdateBranchController extends Controller
{
    public function __invoke(Branch $branch, FormBranchRequest $request)
    {
        $user = Auth::user();
        abort_if($branch->team_id !== $user->team_id, 403);

        if ($request->boolean('is_default')) {
            Branch::where('team_id', $user->team_id)
                ->where('season_id', $branch->season_id)
                ->where('id', '!=', $branch->id)
                ->update(['is_default' => false]);
        }

        $branch->name       = $request->name;
        $branch->is_default = $request->boolean('is_default');
        $branch->save();
    }
}
