<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fields\UpdateFieldRequest;
use App\Models\Field;
use App\Traits\CheckSeasonLocked;


class UpdateFieldController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Field $field, UpdateFieldRequest $request)
    {
        $this->abortIfSeasonLocked();
        $field->product_name = $request->product_name;
        $field->price        = $request->price;
        $field->quantity     = $request->quantity;
        $field->observations = $request->observations;
        $field->subfamily_id = $request->subfamily_id;
        $field->unit_id      = $request->unit_id;
        $field->branch_id    = $request->branch_id ?: null;
        $field->team_id   = \App\Models\User::find(auth()->id())->team_id;
        $field->user_id   = auth()->user()->id;
        $field->season_id = session('season_id');
        $field->save();

        $field->items()->delete();
        foreach($request->get('months') as $month){
            $field->items()->create(['month_id' => $month]);
        }
    }
}
