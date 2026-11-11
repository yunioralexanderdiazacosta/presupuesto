<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_dispatches', function (Blueprint $table) {
            // Razón social que emite la guía de despacho
            $table->foreignId('company_reason_id')->nullable()->after('team_id')
                ->constrained('company_reasons')->nullOnDelete();
        });

        // Las guías existentes quedan con la razón social del primer cuartel que despacharon
        // (dato aproximado: se puede corregir editando cada guía)
        DB::table('production_dispatches')->orderBy('id')->get(['id'])->each(function ($dispatch) {
            $reasonId = DB::table('production_dispatch_cost_centers as pdcc')
                ->join('cost_center_varieties as ccv', 'ccv.id', '=', 'pdcc.cost_center_variety_id')
                ->join('cost_centers as cc', 'cc.id', '=', 'ccv.cost_center_id')
                ->where('pdcc.production_dispatch_id', $dispatch->id)
                ->whereNotNull('cc.company_reason_id')
                ->orderBy('pdcc.id')
                ->value('cc.company_reason_id');

            if ($reasonId) {
                DB::table('production_dispatches')->where('id', $dispatch->id)->update(['company_reason_id' => $reasonId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_dispatches', function (Blueprint $table) {
            $table->dropForeign(['company_reason_id']);
            $table->dropColumn('company_reason_id');
        });
    }
};
