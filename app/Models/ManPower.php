<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManPower extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'workday', 'observations', 'subfamily_id'];
}
