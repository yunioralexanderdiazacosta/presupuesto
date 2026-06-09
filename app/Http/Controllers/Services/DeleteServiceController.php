<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\CheckSeasonLocked;

class DeleteServiceController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Service $service)
    {
        $this->abortIfSeasonLocked();
        $service->items()->detach();
        $service->delete();
    }
}
