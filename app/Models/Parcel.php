<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'observations', 'company_reason_id', 'season_id', 'team_id'];

    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    } 
}
