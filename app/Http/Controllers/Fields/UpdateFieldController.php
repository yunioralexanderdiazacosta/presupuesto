<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fields\UpdateFieldRequest;
use App\Models\Field;


class UpdateFieldController extends Controller
{
    public function __invoke(Field $field, UpdateFieldRequest $request)
    {
        $field->product_name = $request->product_name;
        $field->price        = $request->price;
        $field->quantity     = $request->quantity;
        $field->observations = $request->observations;
        $field->subfamily_id = $request->subfamily_id;
        $field->unit_id      = $request->unit_id;
         $field->team_id = auth()->user()->team_id;
        $field->save(); 

        $field->items()->delete();
        foreach($request->get('months') as $month){
            $field->items()->create(['month_id' => $month]);
        }
    }
}
