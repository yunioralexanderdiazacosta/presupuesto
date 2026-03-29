<?php

namespace App\Http\Controllers\AiChat;

use App\Http\Controllers\Controller;
use App\Services\DatabaseChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiDatabaseChatController extends Controller
{
    public function __invoke(Request $request, DatabaseChatService $service): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $seasonId = session('season_id');

        if (!$seasonId) {
            return response()->json([
                'error' => 'No hay una temporada activa seleccionada. Por favor selecciona una temporada primero.',
            ], 422);
        }

        try {
            $result = $service->answer(
                $request->input('question'),
                (int) Auth::user()->team_id,
                (int) $seasonId
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('❌ AI Chat error', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'Ocurrió un error al procesar tu consulta: ' . $e->getMessage(),
            ], 500);
        }
    }
}
