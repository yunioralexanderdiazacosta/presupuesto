<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exporter;
use Illuminate\Support\Facades\Auth;

class GetExportersController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Exporter::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'rut'])
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->name . ($e->rut ? ' (' . $e->rut . ')' : ''),
            ]);
    }
}
