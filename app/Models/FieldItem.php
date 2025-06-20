<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FieldItem extends Pivot
{
    use HasFactory;

    protected $table = 'field_items';
}
