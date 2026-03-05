<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class SystemGuideController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('SystemGuide');
    }
}
