<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'user_id',
        'number',
        'date',
        'character',
        'concept_observations',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'resolved_at' => 'datetime',
    ];

    // ─── Relaciones ──────────────────────────────

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'payment_request_cost_center');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'payment_request_user');
    }

    public function files()
    {
        return $this->hasMany(PaymentRequestFile::class);
    }

    // ─── Accessors ───────────────────────────────

    public function getCharacterLabelAttribute()
    {
        return match ($this->character) {
            'normal' => 'Normal',
            'importante' => 'Importante',
            'urgente' => 'Urgente',
            default => $this->character,
        };
    }

    public function getCharacterColorAttribute()
    {
        return match ($this->character) {
            'normal' => 'secondary',
            'importante' => 'warning',
            'urgente' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'gestionada' => 'Gestionada',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pendiente' => 'info',
            'gestionada' => 'success',
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
            return 'SP-001';
        }

        $num = (int) str_replace('SP-', '', $last);
        return 'SP-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
