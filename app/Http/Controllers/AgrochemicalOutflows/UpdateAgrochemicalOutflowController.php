<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        DB::beginTransaction();

        try {
            foreach ($request->detalle as $item) {
                $outflow = AgrochemicalOutflow::where('id', $item['id'])
                    ->where('team_id', $teamId)
                    ->firstOrFail();

                $outflow->update([
                    'date' => $request->date,
                    'maquinadas' => $request->maquinadas,
                    'observations' => $request->observations,
                    'quantity' => $item['cantidad'],
                ]);

                // Actualizar también el outflow del kardex si existe
                $kardex = Outflow::where('agrochemical_outflow_id', $outflow->id)->first();
                if ($kardex) {
                    $kardex->update([
                        'date' => $request->date,
                        'quantity' => $item['cantidad'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('agrochemical-outflows.index')
                ->with('success', 'Aplicación actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Error al actualizar: ' . $e->getMessage()
            ]);
        }
    }
}
