<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level1;

class DeleteLevelController extends Controller
{
    public function __invoke(Level1 $level)
    {
        $level->delete();
    }
}
