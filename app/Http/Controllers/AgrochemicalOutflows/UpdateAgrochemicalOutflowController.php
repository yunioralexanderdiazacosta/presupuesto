<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\Outflow;
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Traits\CheckSeasonLocked;

class UpdateAgrochemicalOutflowController
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;

        $request->validate([
            'application_order_id' => 'required|integer',
            'date' => 'required|date',
            'maquinadas' => 'required|numeric|min:0.01',
            'observations' => 'nullable|string|max:500',
            'detalle' => 'required|array|min:1',
            'detalle.*.id' => 'required|exists:agrochemical_outflows,id',
            'detalle.*.cantidad' => 'required|numeric|min:0',
        ]);

        $applicationOrderId = (int) $request->application_order_id;

        DB::beginTransaction();

        try {
            foreach ($request->detalle as $item) {
                $outflow = AgrochemicalOutflow::where('id', $item['id'])
                    ->where('team_id', $teamId)
                    ->where('application_order_id', $applicationOrderId)
                    ->firstOrFail();

                $outflow->update([
                    'date' => $request->date,
                    'maquinadas' => $request->maquinadas,
                    'observations' => $request->observations,
                    'quantity' => $item['cantidad'],
                ]);
            }

            // Cada línea de factura genera un registro por cuartel (cantidad prorrateada) y UN solo
            // movimiento de kardex con el total, vinculado al último registro de cuartel de esa línea.
            // Por eso el kardex se recalcula sumando los cuarteles de cada línea, nunca con un cuartel suelto.
            $rows = AgrochemicalOutflow::where('application_order_id', $applicationOrderId)
                ->where('team_id', $teamId)
                ->orderBy('id')
                ->get(['id', 'invoice_product_id', 'quantity']);

            $kardexByRow = Outflow::whereIn('agrochemical_outflow_id', $rows->pluck('id'))
                ->get()
                ->keyBy('agrochemical_outflow_id');

            $newQtyByKardex = [];
            $accumulated = [];
            foreach ($rows as $row) {
                $lineKey = (int) $row->invoice_product_id;
                $accumulated[$lineKey] = ($accumulated[$lineKey] ?? 0) + (float) $row->quantity;
                if ($kardex = $kardexByRow->get($row->id)) {
                    $newQtyByKardex[$kardex->id] = round($accumulated[$lineKey], 4);
                    $accumulated[$lineKey] = 0;
                }
            }

            // Validar stock solo si la aplicación consume más que antes en algún lote
            $requestedByLine = [];
            $previousByLine = [];
            foreach ($kardexByRow as $kardex) {
                if (!$kardex->invoice_product_id) {
                    continue;
                }
                $lineId = (int) $kardex->invoice_product_id;
                $requestedByLine[$lineId] = ($requestedByLine[$lineId] ?? 0) + ($newQtyByKardex[$kardex->id] ?? (float) $kardex->quantity);
                $previousByLine[$lineId] = ($previousByLine[$lineId] ?? 0) + (float) $kardex->quantity;
            }
            InventoryAvailabilityService::assertCanConsume(
                $requestedByLine,
                [],
                $kardexByRow->pluck('id')->all(),
                'stock',
                $previousByLine
            );

            foreach ($kardexByRow as $kardex) {
                $kardex->update([
                    'date' => $request->date,
                    'quantity' => $newQtyByKardex[$kardex->id] ?? $kardex->quantity,
                ]);
            }

            DB::commit();

            return redirect()->route('agrochemical-outflows.index')
                ->with('success', 'Aplicación actualizada correctamente.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Error al actualizar: ' . $e->getMessage()
            ]);
        }
    }
}
