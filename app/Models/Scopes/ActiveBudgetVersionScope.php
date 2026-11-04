<?php

namespace App\Models\Scopes;

use App\Support\ActiveBudgetVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restringe toda consulta Eloquent de una línea de presupuesto a la versión activa del usuario.
 *
 * Sin versión activa (consola, seeders, sin sesión) no filtra nada. Para leer líneas de otra
 * versión de forma deliberada (copiar, comparar) usar ->withoutGlobalScope(ActiveBudgetVersionScope::class).
 */
class ActiveBudgetVersionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $versionId = ActiveBudgetVersion::id();

        if (!$versionId) {
            return;
        }

        $column = ActiveBudgetVersion::qualifiedColumn($builder->getQuery()->from, $model->getTable());

        $builder->where($column, $versionId);
    }
}
