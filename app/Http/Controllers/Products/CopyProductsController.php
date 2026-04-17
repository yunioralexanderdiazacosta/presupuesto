<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Level1;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\Product;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CopyProductsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('copy-products')) {
            abort(403, 'No tiene permiso para copiar productos.');
        }

        $request->validate([
            'source_team_id' => 'required|integer|exists:teams,id',
        ]);

        $sourceTeamId = $request->source_team_id;
        $targetTeamId = $user->team_id;
        $seasonId = session('season_id');

        if ($sourceTeamId == $targetTeamId) {
            return back()->withErrors(['error' => 'No puede copiar productos del mismo equipo.']);
        }

        // Obtener productos del equipo origen
        $sourceProducts = Product::where('team_id', $sourceTeamId)
            ->with('unit', 'level1', 'level2', 'level3')
            ->get();

        if ($sourceProducts->isEmpty()) {
            return back()->withErrors(['error' => 'El equipo origen no tiene productos.']);
        }

        // Construir mapas de niveles del equipo destino por nombre
        $targetLevel1s = Level1::where('team_id', $targetTeamId)
            ->where('season_id', $seasonId)
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [mb_strtolower($name) => $id]);

        $targetLevel2sByParent = [];
        $level2s = Level2::whereIn('level1_id', Level1::where('team_id', $targetTeamId)->where('season_id', $seasonId)->pluck('id'))
            ->get();
        foreach ($level2s as $l2) {
            $key = $l2->level1_id . '|' . mb_strtolower($l2->name);
            $targetLevel2sByParent[$key] = $l2->id;
        }

        $targetLevel3sByParent = [];
        $level3s = Level3::whereIn('level2_id', $level2s->pluck('id'))->get();
        foreach ($level3s as $l3) {
            $key = $l3->level2_id . '|' . mb_strtolower($l3->name);
            $targetLevel3sByParent[$key] = $l3->id;
        }

        // Mapa de unidades por nombre (son globales)
        $unitsByName = Unit::pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [mb_strtolower($name) => $id]);

        // Productos existentes en destino por nombre (para evitar duplicados)
        $existingNames = Product::where('team_id', $targetTeamId)
            ->pluck('name')
            ->map(fn($n) => mb_strtolower($n))
            ->toArray();

        $copied = 0;
        $skippedDuplicate = 0;
        $skippedNoHierarchy = 0;
        $warnings = [];

        DB::transaction(function () use (
            $sourceProducts, $targetTeamId, $targetLevel1s, $targetLevel2sByParent,
            $targetLevel3sByParent, $unitsByName, $existingNames,
            &$copied, &$skippedDuplicate, &$skippedNoHierarchy, &$warnings
        ) {
            foreach ($sourceProducts as $product) {
                // Verificar duplicado
                if (in_array(mb_strtolower($product->name), $existingNames)) {
                    $skippedDuplicate++;
                    continue;
                }

                // Mapear Unit
                $unitId = $product->unit_id;
                if ($product->unit) {
                    $mappedUnit = $unitsByName[mb_strtolower($product->unit->name)] ?? null;
                    if ($mappedUnit) {
                        $unitId = $mappedUnit;
                    }
                }

                // Mapear Level1
                $targetL1Id = null;
                if ($product->level1) {
                    $targetL1Id = $targetLevel1s[mb_strtolower($product->level1->name)] ?? null;
                    if (!$targetL1Id) {
                        $skippedNoHierarchy++;
                        $warnings[] = "'{$product->name}': Nivel 1 '{$product->level1->name}' no existe en destino";
                        continue;
                    }
                }

                // Mapear Level2
                $targetL2Id = null;
                if ($product->level2 && $targetL1Id) {
                    $key = $targetL1Id . '|' . mb_strtolower($product->level2->name);
                    $targetL2Id = $targetLevel2sByParent[$key] ?? null;
                    if (!$targetL2Id) {
                        $skippedNoHierarchy++;
                        $warnings[] = "'{$product->name}': Nivel 2 '{$product->level2->name}' no existe en destino";
                        continue;
                    }
                }

                // Mapear Level3
                $targetL3Id = null;
                if ($product->level3 && $targetL2Id) {
                    $key = $targetL2Id . '|' . mb_strtolower($product->level3->name);
                    $targetL3Id = $targetLevel3sByParent[$key] ?? null;
                    if (!$targetL3Id) {
                        $skippedNoHierarchy++;
                        $warnings[] = "'{$product->name}': Nivel 3 '{$product->level3->name}' no existe en destino";
                        continue;
                    }
                }

                Product::create([
                    'name' => $product->name,
                    'active_ingredient' => $product->active_ingredient,
                    'team_id' => $targetTeamId,
                    'unit_id' => $unitId,
                    'level1_id' => $targetL1Id,
                    'level2_id' => $targetL2Id,
                    'level3_id' => $targetL3Id,
                    'level4_id' => $product->level4_id,
                ]);

                $existingNames[] = mb_strtolower($product->name);
                $copied++;
            }
        });

        $message = "{$copied} productos copiados correctamente.";
        if ($skippedDuplicate > 0) {
            $message .= " {$skippedDuplicate} omitidos por duplicado.";
        }
        if ($skippedNoHierarchy > 0) {
            $message .= " {$skippedNoHierarchy} omitidos por jerarquía faltante.";
        }

        return back()->with([
            'success' => $message,
            'copy_warnings' => $warnings,
        ]);
    }

    /**
     * Preview: cuántos productos se copiarían desde el equipo origen.
     */
    public function preview(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('copy-products')) {
            return response()->json(['error' => 'Sin permiso'], 403);
        }

        $request->validate([
            'source_team_id' => 'required|integer|exists:teams,id',
        ]);

        $sourceTeamId = $request->source_team_id;
        $targetTeamId = $user->team_id;

        $totalSource = Product::where('team_id', $sourceTeamId)->count();
        $existingNames = Product::where('team_id', $targetTeamId)
            ->pluck('name')
            ->map(fn($n) => mb_strtolower($n));

        $sourceNames = Product::where('team_id', $sourceTeamId)
            ->pluck('name')
            ->map(fn($n) => mb_strtolower($n));

        $duplicates = $sourceNames->intersect($existingNames)->count();
        $newProducts = $totalSource - $duplicates;

        return response()->json([
            'total_source' => $totalSource,
            'duplicates' => $duplicates,
            'new_products' => $newProducts,
        ]);
    }
}
