<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormProductRequest;
use App\Models\Product;

class StoreProductController extends Controller
{
    public function __invoke(FormProductRequest $request)
    {
        $user = Auth::user();

        Product::Create([
            'name'      => $request->name,
            'unit_id'   => $request->unit_id,
            'level1_id'   => $request->level1_id,
            'level2_id'   => $request->level2_id,
            'level3_id'   => $request->level3_id,
            'level4_id'   => $request->level4_id,
            'team_id'   => $user->team_id
        ]);
    }
}
