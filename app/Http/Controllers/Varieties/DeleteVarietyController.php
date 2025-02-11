<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Variety;

class DeleteVarietyController extends Controller
{
    public function __invoke(Variety $variety)
    {
        $variety->delete();
    }
}
