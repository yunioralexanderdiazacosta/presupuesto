<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fields\UpdateFieldRequest;
use App\Models\Field;


class UpdateFieldController extends Controller
{
    public function __invoke(Field $field, UpdateFieldRequest $request)
    {
        $administration->product_name = $request->product_name;
        $administration->price        = $request->price;
        $administration->quantity     = $request->quantity;
        $administration->observations = $request->observations;
        $administration->subfamily_id = $request->subfamily_id;
        $administration->unit_id      = $request->unit_id;
        $administration->save(); 

        $administration->items()->delete();
        foreach($request->get('months') as $month){
            $administration->items()->create(['month_id' => $month]);
        }
    }
}
