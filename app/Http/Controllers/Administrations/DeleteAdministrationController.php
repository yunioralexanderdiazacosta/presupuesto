<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Models\Administration;
use App\Traits\CheckSeasonLocked;

class DeleteAdministrationController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Administration $administration)
    {
        $this->abortIfSeasonLocked();
        $administration->items()->delete();
        $administration->delete();
       
    }
}
