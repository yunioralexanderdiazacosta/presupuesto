<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Models\Supply;


class DeleteSupplyController extends Controller
{
    public function __invoke(Supply $supply)
    {
        $supply->items()->detach();
        $supply->delete();
    }
}
