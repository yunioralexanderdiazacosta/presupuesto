<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\ImportLevel;
use Illuminate\Support\Facades\Auth;

class ImportLevel3Controller extends Controller
{
    public function __invoke(Level2 $level2)
    {
        $user = Auth::user();

        // Validar si ya existen registros de Level3 para el Level2 destino
        if (Level3::where('level2_id', $level2->id)->exists()) {
            return response()->json(['error' => 'Ya existen registros de Nivel 3 para este Nivel 2.'], 409);
        }

        // Buscar el Level1 asociado a este Level2
        $level1 = $level2->level1;
        if (!$level1) {
            return response()->json(['error' => 'No se encontró el Level1 asociado.'], 404);
        }

        // Buscar el Level1Template por nombre (o puedes usar otro campo si tienes una relación directa)
        $level1Template = \App\Models\Level1Template::where('name', $level1->name)->first();
        if (!$level1Template) {
            return response()->json(['error' => 'No se encontró plantilla de Level1.'], 404);
        }

        // Buscar el Level2Template por nombre
        $level2Template = \App\Models\Level2Template::where('level1_template_id', $level1Template->id)
            ->where('name', $level2->name)
            ->first();
        if (!$level2Template) {
            return response()->json(['error' => 'No se encontró plantilla de Level2.'], 404);
        }

        // Obtener los Level3Template asociados
        $level3Templates = \App\Models\Level3Template::where('level2_template_id', $level2Template->id)->get();

        foreach($level3Templates as $level3Template){
            Level3::create([
                'level2_id' => $level2->id,
                'name' => $level3Template->name,
                'order' => $level3Template->order ?? null,
            ]);
        }

        \App\Models\ImportLevel::create([
            'user_id'   => $user->id,
            'level2_id' => $level2->id
        ]);
    }
}
