<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Traits\CheckSeasonLocked;


class DeleteFieldController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Field $field)
    {
        $this->abortIfSeasonLocked();
        $field->items()->delete();
        $field->delete();
    }
}
