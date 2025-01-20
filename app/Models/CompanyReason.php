<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyReason extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rut', 'legal_representative', 'rut_representative','address', 'team_id'];
}
