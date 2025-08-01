<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'team_id', 'unit_id', 'level1_id', 'level2_id', 'level3_id', 'level4_id'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class);
    }


    public function level2()
    {
        return $this->belongsTo(Level2::class);

    } 
    public function level3()
    {
        return $this->belongsTo(Level3::class);
    }
}
