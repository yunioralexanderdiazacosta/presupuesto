<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Models\Administration;

class DeleteAdministrationController extends Controller
{
    public function __invoke(Administration $administration)
    {
        $administration->items()->delete();
        $administration->delete();
       
    }
}
