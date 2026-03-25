<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\VarietyCostParam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpsertVarietyCostParamsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'params'                  => 'required|array',
            'params.*.variety_id'     => 'required|integer|exists:varieties,id',
            'params.*.pct_embalaje'   => 'required|numeric|min:0|max:100',
            'params.*.precio_proceso' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['params'] as $item) {
            VarietyCostParam::updateOrCreate(
                [
                    'team_id'    => $user->team_id,
                    'variety_id' => $item['variety_id'],
                ],
                [
                    'pct_embalaje'   => $item['pct_embalaje'],
                    'precio_proceso' => $item['precio_proceso'],
                ]
            );
        }

        return back();
    }
}
