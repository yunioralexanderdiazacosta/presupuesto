<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use App\Models\Field;


class DeleteFieldController extends Controller
{
    public function __invoke(Field $field)
    {
        $field->items()->delete();
        $field->delete();
    }
}
