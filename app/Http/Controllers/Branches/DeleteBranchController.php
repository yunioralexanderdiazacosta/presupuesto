<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class DeleteBranchController extends Controller
{
    public function __invoke(Branch $branch)
    {
        $user = Auth::user();
        abort_if($branch->team_id !== $user->team_id, 403);

        $branch->delete();
    }
}
