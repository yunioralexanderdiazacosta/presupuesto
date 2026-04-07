<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use App\Models\RnpPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpsertRnpPricesController extends Controller
{
    public function __invoke(ProjectEvaluation $projectEvaluation, Request $request)
    {
        $user = Auth::user();
        abort_if((int) $projectEvaluation->team_id !== (int) $user->team_id, 403);

        $data = $request->validate([
            'prices'              => 'required|array',
            'prices.*.variety_id' => 'required|integer|exists:varieties,id',
            'prices.*.week'       => 'required|integer|min:1|max:53',
            'prices.*.price_usd'  => 'required|numeric|min:0',
        ]);

        foreach ($data['prices'] as $item) {
            RnpPrice::updateOrCreate(
                [
                    'project_evaluation_id' => $projectEvaluation->id,
                    'variety_id' => $item['variety_id'],
                    'week'       => $item['week'],
                ],
                [
                    'team_id'   => $user->team_id,
                    'price_usd' => $item['price_usd'],
                ]
            );
        }

        return back();
    }
}
