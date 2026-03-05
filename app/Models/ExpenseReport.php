<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'user_id',
        'assigned_to',
        'number',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejection_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'assigned_to' => 'integer',
        'approved_by' => 'integer',
        'user_id' => 'integer',
    ];

    // ─── Relaciones ──────────────────────────────

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function season()
    {
        return $this->belongsTo(\App\Models\Season::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ExpenseReportItem::class);
    }

    // ─── Accessors ───────────────────────────────

    /**
     * Total de la rendición (suma de items)
     */
    public function getTotalAmountAttribute()
    {
        return $this->items()->sum('amount');
    }

    /**
     * Total contabilizado (items que ya tienen invoice_id)
     */
    public function getContabilizedAmountAttribute()
    {
        return $this->items()->whereNotNull('invoice_id')->sum('amount');
    }

    /**
     * Total pendiente de contabilizar
     */
    public function getPendingAmountAttribute()
    {
        return $this->total_amount - $this->contabilized_amount;
    }

    /**
     * Cantidad de items
     */
    public function getItemsCountAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Label del estado para la UI
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'borrador' => 'Borrador',
            'enviada' => 'Enviada',
            'aprobada' => 'Aprobada',
            'pagada' => 'Pagada',
            'rechazada' => 'Rechazada',
            default => $this->status,
        };
    }

    /**
     * Color badge del estado
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'borrador' => 'secondary',
            'enviada' => 'info',
            'aprobada' => 'primary',
            'pagada' => 'success',
            'rechazada' => 'danger',
            default => 'secondary',
        };
    }

    // ─── Helpers ─────────────────────────────────

    /**
     * Generar siguiente número correlativo para el equipo y temporada
     */
    public static function nextNumber($team_id, $season_id)
    {
        $last = static::where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->orderByDesc('id')
            ->value('number');

        if (!$last) {
            return 'RG-001';
        }

        $num = (int) str_replace('RG-', '', $last);
        return 'RG-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
