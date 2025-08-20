<?php

namespace App\Http\Controllers\Consumptions;

use App\Http\Controllers\Controller;
use App\Models\Consumption;
use App\Http\Requests\Consumptions\UpdateConsumptionRequest;
use Illuminate\Support\Facades\DB;

class UpdateConsumptionController extends Controller
{
    public function __invoke(UpdateConsumptionRequest $request, Consumption $consumption)
    {
    $data = $request->validated();

        DB::transaction(function () use ($data, $consumption) {
            $consumption->update($data);
            // Eliminar items que no están en la petición
            $ids = collect($data['items'])->pluck('id')->filter()->all();
            $consumption->items()->whereNotIn('id', $ids)->delete();
            // Actualizar o crear items
            foreach ($data['items'] as $item) {
                if (isset($item['id'])) {
                    $consumption->items()->where('id', $item['id'])->update($item);
                } else {
                    $consumption->items()->create($item);
                }
            }
        });

        return response()->noContent();
    }
}
