<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fertilizer;

class DeleteFertilizerController extends Controller
{
    public function __invoke(Fertilizer $fertilizer)
    {
        $fertilizer->items()->detach();
        $fertilizer->delete();
    }
}
