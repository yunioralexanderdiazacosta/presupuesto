<?php

namespace App\Http\Controllers\Groupings;

use App\Http\Controllers\Controller;
use App\Models\Grouping;

class DeleteGroupingController extends Controller
{
    public function __invoke(Grouping $grouping)
    {
        $grouping->items()->detach();
        $grouping->delete();
    }
}
