<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Traits\CheckSeasonLocked;


class DeleteSupplyController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Supply $supply)
    {
        $this->abortIfSeasonLocked();
        $supply->items()->detach();
        $supply->delete();
    }
}
