<?php

namespace Tests\Unit;

use App\Models\Concerns\BelongsToBudgetVersion;
use App\Services\BudgetCopyService;
use App\Support\ActiveBudgetVersion;
use Illuminate\Database\Query\Expression;
use PHPUnit\Framework\TestCase;

class ActiveBudgetVersionTest extends TestCase
{
    public function test_column_uses_the_table_alias_when_present(): void
    {
        $this->assertSame('a.budget_version_id', ActiveBudgetVersion::qualifiedColumn('agrochemicals as a', 'agrochemicals'));
        $this->assertSame('f.budget_version_id', ActiveBudgetVersion::qualifiedColumn('fields AS f', 'fields'));
    }

    public function test_column_uses_the_table_name_without_alias(): void
    {
        $this->assertSame('fields.budget_version_id', ActiveBudgetVersion::qualifiedColumn('fields', 'fields'));
    }

    public function test_column_falls_back_to_the_model_table_for_expressions(): void
    {
        $subquery = new Expression('(select 1) as x');

        $this->assertSame('services.budget_version_id', ActiveBudgetVersion::qualifiedColumn($subquery, 'services'));
    }

    /**
     * Si un modelo de línea de presupuesto pierde el trait, sus consultas dejan de filtrar por versión
     * y los totales se duplicarían en silencio cuando exista más de una versión.
     */
    public function test_every_budget_line_model_is_scoped_by_version(): void
    {
        foreach (BudgetCopyService::TYPES as $type => $config) {
            $this->assertContains(
                BelongsToBudgetVersion::class,
                class_uses($config['model']),
                "{$config['model']} ({$type}) debe usar BelongsToBudgetVersion"
            );
        }
    }
}
