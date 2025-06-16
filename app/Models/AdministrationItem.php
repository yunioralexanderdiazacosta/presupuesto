<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdministrationItem extends Pivot
{
    use HasFactory;
    protected $table = 'administration_items';
}
