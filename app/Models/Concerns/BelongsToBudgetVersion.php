<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ActiveBudgetVersionScope;
use App\Support\ActiveBudgetVersion;

/**
 * Para los modelos de líneas de presupuesto (agroquímicos, fertilizantes, insumos, servicios,
 * cosecha, mano de obra, administración y generales de campo).
 *
 * - Toda consulta se limita a la versión activa (ver ActiveBudgetVersionScope).
 * - Toda línea nueva queda en la versión activa, salvo que se indique otra explícitamente.
 */
trait BelongsToBudgetVersion
{
    protected static function bootBelongsToBudgetVersion(): void
    {
        static::addGlobalScope(new ActiveBudgetVersionScope());

        static::creating(function ($model) {
            if (empty($model->budget_version_id)) {
                $model->budget_version_id = ActiveBudgetVersion::id();
            }
        });
    }
}
