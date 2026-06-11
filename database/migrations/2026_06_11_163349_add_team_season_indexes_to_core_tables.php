<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices: filtro principal en InvoicesController
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['team_id', 'season_id'], 'invoices_team_season_index');
        });

        // outflows: filtro principal en OutflowsController
        Schema::table('outflows', function (Blueprint $table) {
            $table->index(['team_id', 'season_id'], 'outflows_team_season_index');
        });

        // credit_debit_notes: consultada en InvoicesController y OutflowsController
        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->index(['team_id', 'season_id'], 'credit_debit_notes_team_season_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_team_season_index');
        });

        Schema::table('outflows', function (Blueprint $table) {
            $table->dropIndex('outflows_team_season_index');
        });

        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->dropIndex('credit_debit_notes_team_season_index');
        });
    }
};
