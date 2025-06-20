<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'quantity', 'subfamily_id', 'level2_id', 'unit_id', 'observations', 'team_id'];

      public function items()
    {
        return $this->hasMany(FieldItem::class, 'field_id');
    }

    public function subfamily()
    {
        return $this->belongsTo(Level3::class, 'subfamily_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function level2()
    {
        return $this->belongsTo(Level2::class, 'level2_id');
    }
}
