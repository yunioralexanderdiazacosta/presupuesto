<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;

class DeleteServiceController extends Controller
{
    public function __invoke(Service $service)
    {
        $service->items()->detach();
        $service->delete();
    }
}
