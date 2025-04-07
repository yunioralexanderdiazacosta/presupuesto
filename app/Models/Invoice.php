<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['number','payment_term','payment_type','petty_cash','team_id','supplier_id','company_reason_id','type_document_id','number_document','date','due_date', 'season_id'];

    protected $cash = [
        'petty_cash' => 'boolean'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot(['unit_price', 'amount', 'observations'])->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }
}
