<?php

namespace App\Http\Controllers\Consumptions;

use App\Http\Controllers\Controller;
use App\Models\Consumption;
use App\Models\ConsumptionItem;
use App\Http\Requests\Consumptions\StoreConsumptionRequest;
use Illuminate\Support\Facades\DB;

class StoreConsumptionController extends Controller
{
    public function __invoke(StoreConsumptionRequest $request)
    {
    $data = $request->validated();

        DB::transaction(function () use ($data) {
            $consumption = Consumption::create($data);
            foreach ($data['items'] as $item) {
                $consumption->items()->create($item);
            }
        });

        return response()->noContent();
    }
}
