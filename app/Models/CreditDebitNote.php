<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'type',
        'invoice_id',
        'supplier_id',
        'number',
        'date',
        'reason',
        'affects_inventory',
        'user_id',
        'is_annulment',
    ];

    protected $casts = [
        'affects_inventory' => 'boolean',
        'is_annulment' => 'boolean',
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(CreditDebitNoteItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
