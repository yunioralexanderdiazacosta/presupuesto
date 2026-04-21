<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CausalTermino extends Model
{
    protected $table = 'causales_termino';

    protected $fillable = [
        'codigo',
        'nombre',
        'articulo',
        'aplica_faena',
        'activa',
        'orden',
    ];

    protected $casts = [
        'aplica_faena' => 'boolean',
        'activa'       => 'boolean',
    ];

    public function terminations(): HasMany
    {
        return $this->hasMany(Termination::class, 'causal_termino_id');
    }
}
