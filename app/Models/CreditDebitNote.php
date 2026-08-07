<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDebitNote extends Model
{
    use HasFactory;

    public const TYPE_CREDIT = 'credito';
    public const TYPE_DEBIT = 'debito';

    protected $fillable = [
        'team_id',
        'season_id',
        'type',
        'invoice_id',
        'supplier_id',
        'number',
        'date',
        'month_id',
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

    protected static function booted(): void
    {
        // No existe un campo en el formulario para que el usuario elija el "mes contable"
        // de la nota (a diferencia de las facturas), así que se deriva siempre de `date`.
        static::saving(function (CreditDebitNote $note) {
            if ($note->date) {
                $note->month_id = (int) date('n', strtotime($note->date));
            }
        });
    }

    public function scopeAffectingInventory($query)
    {
        return $query->where('affects_inventory', true);
    }

    /** Monto total de la nota con signo: negativo si es crédito (NC), positivo si es débito (ND). */
    public function signedAmount(): float
    {
        $total = (float) $this->items()->selectRaw('SUM(quantity * unit_price) as total')->value('total');
        return $this->type === self::TYPE_CREDIT ? -$total : $total;
    }

    /** NC "financiera": no afecta inventario, su descuento ya fue aplicado al unit_price de la factura original. */
    public function isFinancialCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT && !$this->affects_inventory;
    }

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

    public function month()
    {
        return $this->belongsTo(Month::class);
    }
}
