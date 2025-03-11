<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Season;

class DeleteSeasonController extends Controller
{
    public function __invoke(Season $season)
    {
        $season->delete();
    }
}
